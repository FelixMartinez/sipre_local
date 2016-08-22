<?php


function buscar($frmBuscar){
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s|%s|%s",
		$frmBuscar['lstEmpresa'],
		$frmBuscar['txtFechaDesde'],
		$frmBuscar['txtFechaHasta'],
		$frmBuscar['txtCriterio']);
		
	$objResponse->loadCommands(listaPedidoVenta(0, "id_pedido_venta", "DESC", $valBusq));
	$objResponse->loadCommands(listaOrdenServicio(0, "numero_orden", "DESC", $valBusq));
	
	return $objResponse;
}

function cargarPagina($idEmpresa){
	$objResponse = new xajaxResponse();
	
	// VERIFICA VALORES DE CONFIGURACION (Mostrar Cajas por Empresa)
	$queryConfig400 = sprintf("SELECT * FROM pg_configuracion_empresa config_emp
		INNER JOIN pg_configuracion config ON (config_emp.id_configuracion = config.id_configuracion)
	WHERE config.id_configuracion = 400 AND config_emp.status = 1 AND config_emp.id_empresa = %s;",
		valTpDato($idEmpresa, "int"));
	$rsConfig400 = mysql_query($queryConfig400);
	if (!$rsConfig400) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE_);
	$rowConfig400 = mysql_fetch_assoc($rsConfig400);
	
	if ($rowConfig400['valor'] == 1) { // 0 = Caja Propia, 1 = Caja Empresa Principal
		$queryEmpresa = sprintf("SELECT suc.id_empresa_padre FROM pg_empresa suc WHERE suc.id_empresa = %s;",
			valTpDato($idEmpresa, "int"));
		$rsEmpresa = mysql_query($queryEmpresa);
		if (!$rsEmpresa) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE_);
		$rowEmpresa = mysql_fetch_assoc($rsEmpresa);
		
		$idEmpresa = ($rowEmpresa['id_empresa_padre'] > 0) ? $rowEmpresa['id_empresa_padre'] : $idEmpresa;
	}
	
	if ($rowConfig400['valor'] == 0) { // 0 = Caja Propia, 1 = Caja Empresa Principal
		$objResponse->loadCommands(cargaLstEmpresaFinal($idEmpresa, "onchange=\"selectedOption(this.id,'".$idEmpresa."');\""));
	} else {
		$objResponse->loadCommands(cargaLstEmpresaFinal($idEmpresa));
	}
	
	$objResponse->loadCommands(validarAperturaCaja($idEmpresa));
	$objResponse->loadCommands(listaPedidoVenta(0,"id_pedido_venta","DESC",$idEmpresa));
	$objResponse->loadCommands(listaOrdenServicio(0,"numero_orden","DESC",$idEmpresa));
	
	return $objResponse;
}

function devolverOrden($idOrden, $frmListaOrdenServicio){
	$objResponse = new xajaxResponse();
	
	mysql_query("START TRANSACTION;");
	
	$updateSQL = sprintf("UPDATE sa_orden SET
		id_estado_orden = 21,
		id_empleado_aprobacion_factura = NULL
	WHERE id_orden = %s;",
		valTpDato($idOrden, "int"));
	mysql_query("SET NAMES 'utf8';");
	$Result1 = mysql_query($updateSQL);
	if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	mysql_query("SET NAMES 'latin1';");
	
	mysql_query("COMMIT;");
	
	$objResponse->alert('Orden de servicio devuelta con éxito');
	
	$objResponse->loadCommands(listaOrdenServicio(
		$frmListaOrdenServicio['pageNum'],
		$frmListaOrdenServicio['campOrd'],
		$frmListaOrdenServicio['tpOrd'],
		$frmListaOrdenServicio['valBusq']));
	
	return $objResponse;
}

function devolverPedido($idPedido, $frmListaPedidoVenta) {
	$objResponse = new xajaxResponse();
	
	if (!xvalidaAcceso($objResponse,"iv_pedido_venta_list","editar")) { return $objResponse; }
	
	mysql_query("START TRANSACTION;");
	
	// BUSCA LOS DATOS DEL PEDIDO DE VENTA
	$queryPedido = sprintf("SELECT * FROM vw_iv_pedidos_venta
	WHERE id_pedido_venta = %s;",
		valTpDato($idPedido, "int"));
	$rsPedido = mysql_query($queryPedido);
	if (!$rsPedido) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowPedido = mysql_fetch_assoc($rsPedido);
	
	$estatusPedidoVenta = 1; // 0 = Pendiente, 1 = Pedido, 2 = Orden, 3 = Facturado, 4 = Devuelta, 5 = Anulada
	
	// EDITA LOS DATOS DEL PEDIDO
	$updateSQL = sprintf("UPDATE iv_pedido_venta SET
		estatus_pedido_venta = %s,
		id_empleado_aprobador = NULL,
		fecha_aprobacion = NULL
	WHERE id_pedido_venta = %s;",
		valTpDato($estatusPedidoVenta, "int"),
		valTpDato($idPedido, "int"));
	mysql_query("SET NAMES 'utf8';");
	$Result1 = mysql_query($updateSQL);
	if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	mysql_query("SET NAMES 'latin1';");
	
	// SE CONECTA CON EL SISTEMA DE SOLICITUDES
	$Result1 = actualizarEstatusSistemaSolicitud($rowPedido['id_pedido_venta_referencia'], $estatusPedidoVenta);
	if ($Result1[0] != true && strlen($Result1[1]) > 0) {
		return $objResponse->alert($Result1[1]);
	} else if ($Result1[0] == true) {
		$objResponse->alert($Result1[1]);
	}
	
	mysql_query("COMMIT;");
	
	$objResponse->alert("Pedido de venta devuelto con éxito");
	
	$objResponse->loadCommands(listaPedidoVenta(
		$frmListaPedidoVenta['pageNum'],
		$frmListaPedidoVenta['campOrd'],
		$frmListaPedidoVenta['tpOrd'],
		$frmListaPedidoVenta['valBusq']));
	
	return $objResponse;
}

function imprimirPedidoVenta($frmBuscar){
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
		$frmBuscar['txtFechaDesde'],
		$frmBuscar['txtFechaHasta'],
		$frmBuscar['txtCriterio']);
		
	$objResponse->script(sprintf("verVentana('reportes/cjrs_facturacion_repuestos_pdf.php?valBusq=%s',890,550)", $valBusq));
	
	return $objResponse;
}

function imprimirOrdenServicio($frmBuscar){
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
		$frmBuscar['txtFechaDesde'],
		$frmBuscar['txtFechaHasta'],
		$frmBuscar['txtCriterio']);
		
	$objResponse->script(sprintf("verVentana('reportes/cjrs_facturacion_servicios_pdf.php?valBusq=%s',890,550)", $valBusq));
	
	return $objResponse;
}

function listaOrdenServicio($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 20, $totalRows = NULL){
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	// 13 = TERMINADO
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("vw_sa_orden.id_estado_orden = 13 AND vw_sa_orden.modo_factura != 'VALE SALIDA'"); 
			
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(vw_sa_orden.id_empresa = %s
		OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
				WHERE suc.id_empresa = vw_sa_orden.id_empresa))",
			valTpDato($valCadBusq[0],"int"),
			valTpDato($valCadBusq[0],"int"));
	}
	
	if ($valCadBusq[1] != "" && $valCadBusq[2] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("DATE(tiempo_orden) BETWEEN %s AND %s",
			valTpDato(date("Y-m-d", strtotime($valCadBusq[1])),"date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])),"date"));
	}
	
	if ($valCadBusq[3] != "-1" && $valCadBusq[3] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(vw_sa_orden.nombre LIKE %s
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
		(SELECT vw_sa_orden.numero_orden FROM vw_sa_orden WHERE vw_sa_orden.id_orden = sa_retrabajo_orden.id_orden_retrabajo) AS numero_orden_retrabajo,
		IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
	FROM cj_cc_cliente
		INNER JOIN vw_sa_orden ON (cj_cc_cliente.id = vw_sa_orden.id_cliente_pago_orden)
		INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (vw_sa_orden.id_empresa = vw_iv_emp_suc.id_empresa_reg)
		LEFT JOIN sa_retrabajo_orden ON (vw_sa_orden.id_orden = sa_retrabajo_orden.id_orden) %s", $sqlBusq);
	
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
		$htmlTh .= ordenarCampo("xajax_listaOrdenServicio", "13%", $pageNum, "nombre_empresa", $campOrd, $tpOrd, $valBusq, $maxRows, "Empresa");
		$htmlTh .= ordenarCampo("xajax_listaOrdenServicio", "7%", $pageNum, "tiempo_orden_registro", $campOrd, $tpOrd, $valBusq, $maxRows, "Fecha");
		$htmlTh .= ordenarCampo("xajax_listaOrdenServicio", "7%", $pageNum, "numero_orden", $campOrd, $tpOrd, $valBusq, $maxRows, ("Nro. Orden"));
		$htmlTh .= ordenarCampo("xajax_listaOrdenServicio", "7%", $pageNum, "numeracion_recepcion", $campOrd, $tpOrd, $valBusq, $maxRows, ("Nro. Recepción"));
		$htmlTh .= ordenarCampo("xajax_listaOrdenServicio", "7%", $pageNum, "nom_uni_bas", $campOrd, $tpOrd, $valBusq, $maxRows, ("Catálogo"));
		$htmlTh .= ordenarCampo("xajax_listaOrdenServicio", "7%", $pageNum, "placa", $campOrd, $tpOrd, $valBusq, $maxRows, "Placa");
		$htmlTh .= ordenarCampo("xajax_listaOrdenServicio", "13%", $pageNum, "chasis", $campOrd, $tpOrd, $valBusq, $maxRows, "Chasis");
		$htmlTh .= ordenarCampo("xajax_listaOrdenServicio", "7%", $pageNum, "numero_orden_retrabajo", $campOrd, $tpOrd, $valBusq, $maxRows, "Orden Retrabajo");
		$htmlTh .= ordenarCampo("xajax_listaOrdenServicio", "18%", $pageNum, "nombre_cliente", $campOrd, $tpOrd, $valBusq, $maxRows, "Cliente");
		$htmlTh .= ordenarCampo("xajax_listaOrdenServicio", "7%", $pageNum, "nombre_tipo_orden", $campOrd, $tpOrd, $valBusq, $maxRows, "Tipo de Orden");	
		$htmlTh .= ordenarCampo("xajax_listaOrdenServicio", "7%", $pageNum, "total_orden", $campOrd, $tpOrd, $valBusq, $maxRows, "Total Orden");
		$htmlTh .= "<td colspan=\"2\"></td>";
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
				
			$imgGenerarFactura = sprintf("<img class=\"puntero\" src=\"../img/iconos/book_next.png\" title=\"Facturar\" onclick=\"window.open('cjrs_facturacion_devolucion_servicios_form.php?doc_type=3&id=%s&ide=%s&acc=2&cons=%s','_self');\"/>",
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
					$imgEstadoAprobControlCalidad = sprintf("<img class=\"puntero\" id='aprobarOrden".$row['id_orden']."' onclick=\"xajax_aprobarOrdenForm(%s, byId('sobregiro').value, 'aprb_fin_ord', '%s', %s,'%s','%s','%s',%s);\" src=\"../img/iconos/aprob_mecanico.png\" title=\"Aprobacion Control Calidad\"/>",
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
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td>".$row['nombre_empresa']."</td>";
			$htmlTb .= "<td align=\"center\" nowrap=\"nowrap\">".date("d-m-Y",strtotime($row['tiempo_orden_registro']))."</td>";
			$htmlTb .= "<td align=\"right\" idordenoculta=\"".$row['id_orden']."\" idempresaoculta=\"".$row['id_empresa']."\">".$row['numero_orden']."</td>";
			$htmlTb .= "<td align=\"right\" idrecepcionoculto =\"".$row['id_recepcion']."\">".$row['numeracion_recepcion']."</td>";
			$htmlTb .= "<td>".utf8_encode($row['nom_uni_bas'])."</td>";
			$htmlTb .= "<td align=\"center\">".utf8_encode($row['placa'])."</td>";
			$htmlTb .= "<td align=\"center\">".utf8_encode($row['chasis'])."</td>";
			$htmlTb .= "<td align=\"right\" idordenocultaretrabajo =\"".$row['id_orden_retrabajo']."\" >".$row['numero_orden_retrabajo']."</td>";
			$htmlTb .= "<td>".utf8_encode($row['nombre_cliente']." ".$row['apellido_cliente'])."</td>";
			$htmlTb .= ($row['nombre_tipo_orden'] == 'CREDITO') ? "<td align=\"center\" class=\"divMsjAlerta\">" : "<td align=\"center\" class=\"divMsjInfo\">";
			$htmlTb .= $row['nombre_tipo_orden'];
			$htmlTb .= "</td>";
			$htmlTb .= "<td align=\"right\">".number_format($row['total_orden'], 2, ".", ",")."</td>";
			$htmlTb .= "<td>";
				$htmlTb .= sprintf("<a class=\"modalImg\" id=\"aDevolver%s\" rel=\"#divFlotante1\" onclick=\"abrirDivFlotante1(this, 'tblPermiso', 'ac_sa_orden_list', '%s', '%s');\"><img class=\"puntero\" src=\"../img/iconos/ico_return.png\" title=\"Devolver Orden\"/></a>",
					$contFila,
					$row['id_orden'],
					$row['numero_orden']);
			$htmlTb .= "</td>";
			$htmlTb .= sprintf("<td id=\"imgGenerarFactura\" class=\"noprint\">%s</td>", $imgGenerarFactura);
		$html .= "</tr>";
	}
	
	$htmlTf = "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"13\">";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaOrdenServicio(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_cj_rs.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaOrdenServicio(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_cj_rs.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaOrdenServicio(%s,'%s','%s','%s',%s)\">",
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaOrdenServicio(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_cj_rs.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaOrdenServicio(%s,'%s','%s','%s',%s);\">%s</a>",
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
		$htmlTb .= "<td colspan=\"13\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("divListaOrdenServicio","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;
}

function listaPedidoVenta($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 20, $totalRows = NULL){
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("estatus_pedido_venta NOT IN (0,1,3,4)");
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("(estatus_pedido_venta = 2 AND id_empleado_aprobador IS NOT NULL)");
	
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(vw_iv_pedidos_venta.id_empresa = %s
		OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
				WHERE suc.id_empresa = vw_iv_pedidos_venta.id_empresa))",
			valTpDato($valCadBusq[0],"int"),
			valTpDato($valCadBusq[0],"int"));
	}
	
	if ($valCadBusq[1] != "" && $valCadBusq[2] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("fecha BETWEEN %s AND %s",
			valTpDato(date("Y-m-d", strtotime($valCadBusq[1])),"date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])),"date"));
	}
	
	if ($valCadBusq[3] != "-1" && $valCadBusq[3] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(id_pedido_venta_propio LIKE %s
		OR id_pedido_venta_referencia LIKE %s
		OR numeracion_presupuesto LIKE %s
		OR ci_cliente LIKE %s
		OR nombre_cliente LIKE %s)",
			valTpDato("%".$valCadBusq[3]."%", "text"),
			valTpDato("%".$valCadBusq[3]."%", "text"),
			valTpDato("%".$valCadBusq[3]."%", "text"),
			valTpDato("%".$valCadBusq[3]."%", "text"),
			valTpDato("%".$valCadBusq[3]."%", "text"));
	}
	
	$query = sprintf("SELECT *,
		(SELECT COUNT(ped_venta_det.id_pedido_venta) AS items
		FROM iv_pedido_venta_detalle ped_venta_det
		WHERE ped_venta_det.id_pedido_venta = vw_iv_pedidos_venta.id_pedido_venta) AS items,
		
		(SELECT SUM(ped_venta_det.cantidad) AS pedidos
		FROM iv_pedido_venta_detalle ped_venta_det
		WHERE ped_venta_det.id_pedido_venta = vw_iv_pedidos_venta.id_pedido_venta) AS pedidos,
		
		(SELECT SUM(ped_venta_det.pendiente) AS pendientes
		FROM iv_pedido_venta_detalle ped_venta_det
		WHERE ped_venta_det.id_pedido_venta = vw_iv_pedidos_venta.id_pedido_venta) AS pendientes,
		
		(vw_iv_pedidos_venta.subtotal - vw_iv_pedidos_venta.subtotal_descuento) AS total_neto,
		
		(SELECT SUM(ped_vent_iva.subtotal_iva)
		FROM iv_pedido_venta_iva ped_vent_iva
		WHERE ped_vent_iva.id_pedido_venta = vw_iv_pedidos_venta.id_pedido_venta) AS total_iva,
		
		(vw_iv_pedidos_venta.subtotal
			- vw_iv_pedidos_venta.subtotal_descuento
			+ IFNULL((SELECT SUM(ped_vent_gasto.monto) FROM iv_pedido_venta_gasto ped_vent_gasto
					WHERE ped_vent_gasto.id_pedido_venta = vw_iv_pedidos_venta.id_pedido_venta), 0)
			+ IFNULL((SELECT SUM(ped_iva.subtotal_iva) AS total_iva
					FROM iv_pedido_venta_iva ped_iva
					WHERE ped_iva.id_pedido_venta = vw_iv_pedidos_venta.id_pedido_venta), 0)) AS total,
		IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
	FROM
		vw_iv_pedidos_venta
	INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (vw_iv_pedidos_venta.id_empresa = vw_iv_emp_suc.id_empresa_reg) %s", $sqlBusq);
	
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
	
	$htmlTblIni = "<table border=\"0\" width=\"100%\">";
	$htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">";
		$htmlTh .= ordenarCampo("xajax_listaPedidoVenta", "", $pageNum, "estatus_pedido_venta", $campOrd, $tpOrd, $valBusq, $maxRows, "");
		$htmlTh .= ordenarCampo("xajax_listaPedidoVenta", "18%", $pageNum, "nombre_empresa", $campOrd, $tpOrd, $valBusq, $maxRows, "Empresa");
		$htmlTh .= ordenarCampo("xajax_listaPedidoVenta", "8%", $pageNum, "fecha", $campOrd, $tpOrd, $valBusq, $maxRows, "Fecha");
		$htmlTh .= ordenarCampo("xajax_listaPedidoVenta", "8%", $pageNum, "id_pedido_venta_propio", $campOrd, $tpOrd, $valBusq, $maxRows, "Nro. Pedido");
		$htmlTh .= ordenarCampo("xajax_listaPedidoVenta", "8%", $pageNum, "id_pedido_venta_referencia", $campOrd, $tpOrd, $valBusq, $maxRows, "Nro. Referencia");
		$htmlTh .= ordenarCampo("xajax_listaPedidoVenta", "8%", $pageNum, "numeracion_presupuesto", $campOrd, $tpOrd, $valBusq, $maxRows, "Nro. Presupuesto");
		$htmlTh .= ordenarCampo("xajax_listaPedidoVenta", "8%", $pageNum, "numero_siniestro", $campOrd, $tpOrd, $valBusq, $maxRows, "Nro. Siniestro");
		$htmlTh .= ordenarCampo("xajax_listaPedidoVenta", "18%", $pageNum, "nombre_cliente", $campOrd, $tpOrd, $valBusq, $maxRows, "Cliente");
		$htmlTh .= ordenarCampo("xajax_listaPedidoVenta", "8%", $pageNum, "condicion_pago", $campOrd, $tpOrd, $valBusq, $maxRows, "Tipo de Pago");
		$htmlTh .= ordenarCampo("xajax_listaPedidoVenta", "8%", $pageNum, "items", $campOrd, $tpOrd, $valBusq, $maxRows, "Items");
		$htmlTh .= ordenarCampo("xajax_listaPedidoVenta", "8%", $pageNum, "total", $campOrd, $tpOrd, $valBusq, $maxRows, "Total Pedido");
		$htmlTh .= "<td colspan=\"2\"></td>";
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		switch($row['estatus_pedido_venta']) {
			case 2 : $imgEstatusPedido = "<img src=\"../img/iconos/ico_azul.gif\" title=\"Pedido Aprobado\"/>"; break;
			default : $imgEstatusPedido = "";
		}	
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td>".$imgEstatusPedido."</td>";
			$htmlTb .= "<td>".$row['nombre_empresa']."</td>";
			$htmlTb .= "<td align=\"center\">".date("d-m-Y", strtotime($row['fecha']))."</td>";
			$htmlTb .= "<td align=\"right\">".$row['id_pedido_venta_propio']."</td>";
			$htmlTb .= "<td align=\"right\">".$row['id_pedido_venta_referencia']."</td>";
			$htmlTb .= "<td align=\"right\">".$row['numeracion_presupuesto']."</td>";
			$htmlTb .= "<td align=\"right\">".$row['numero_siniestro']."</td>";
			$htmlTb .= "<td>".utf8_encode($row['nombre_cliente'])."</td>";
			$htmlTb .= ($row['condicion_pago'] == 0) ? "<td align=\"center\" class=\"divMsjAlerta\">" : "<td align=\"center\" class=\"divMsjInfo\">";
			$htmlTb .= ($row['condicion_pago'] == 0) ? "CRÉDITO": "CONTADO";
			$htmlTb .= "</td>";
			$htmlTb .= "<td align=\"center\">".$row['items']."</td>";
			$htmlTb .= "<td align=\"right\">".number_format($row['total'], 2, ".", ",")."</td>";
			$htmlTb .= sprintf("<td><img class=\"puntero\" id=\"imgDevolverPedido%s\" onclick=\"validarDevolverPedido('%s', '%s')\" src=\"../img/iconos/ico_return.png\" title=\"Devolver Pedido\"/></td>",
				$contFila,
				$row['id_pedido_venta'],
				$contFila);
			$htmlTb .= "<td>";
				$htmlTb .= sprintf("<img class=\"puntero\" onclick=\"window.open('cjrs_facturacion_repuestos_form.php?id=%s', '_self');\" src=\"../img/iconos/book_next.png\" title=\"Facturar\"/>", $row['id_pedido_venta']);
			$htmlTb .= "</td>";
		$htmlTb .= "</tr>";
	}
	
	$htmlTf = "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"13\">";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaPedidoVenta(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_cj_rs.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaPedidoVenta(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_cj_rs.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaPedidoVenta(%s,'%s','%s','%s',%s)\">",
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaPedidoVenta(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_cj_rs.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaPedidoVenta(%s,'%s','%s','%s',%s);\">%s</a>",
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
		$htmlTb .= "<td colspan=\"13\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("divListaPedidoVenta","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;
}

function validarAperturaCaja($idEmpresa){
	$objResponse = new xajaxResponse();
	
	$fecha = date("Y-m-d");
	
	//VERIFICA SI LA CAJA TIENE CIERRE - Verifica alguna caja abierta con fecha diferente a la actual.
	//statusAperturaCaja: 0 = CERRADA ; 1 = ABIERTA ; 2 = CERRADA PARCIAL
	$queryCierreCaja = sprintf("SELECT fechaAperturaCaja FROM sa_iv_apertura WHERE statusAperturaCaja <> 0 AND fechaAperturaCaja NOT LIKE %s AND id_empresa = %s",
		valTpDato(date("Y-m-d", strtotime($fecha)), "date"),
		valTpDato($idEmpresa, "int"));
	$rsCierreCaja = mysql_query($queryCierreCaja);
	if (!$rsCierreCaja) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	
	if (mysql_num_rows($rsCierreCaja) > 0){
		$rowCierreCaja = mysql_fetch_array($rsCierreCaja);
		$fechaUltimaApertura = date("d-m-Y",strtotime($rowCierreCaja['fechaAperturaCaja']));
		$objResponse->alert("Debe cerrar la caja del dia: ".$fechaUltimaApertura.".");
		
	} else {
		// VERIFICA SI LA CAJA TIENE APERTURA
		// statusAperturaCaja: 0 = CERRADA ; 1 = ABIERTA ; 2 = CERRADA PARCIAL
		$queryVerificarApertura = sprintf("SELECT * FROM sa_iv_apertura WHERE fechaAperturaCaja = %s AND statusAperturaCaja <> 0 AND id_empresa = %s",
			valTpDato(date("Y-m-d", strtotime($fecha)), "date"),
			valTpDato($idEmpresa, "int"));
		$rsVerificarApertura = mysql_query($queryVerificarApertura);
		if (!$rsVerificarApertura) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		
		if (mysql_num_rows($rsVerificarApertura) == 0){
			$objResponse->alert("Esta caja no tiene apertura.");
		}
	}
	
	return $objResponse;
}

function validarPermiso($frmPermiso){
	$objResponse = new xajaxResponse();
	
	$queryPermiso = sprintf("SELECT *
	FROM pg_empleado empleado
		INNER JOIN pg_usuario usuario ON (empleado.id_empleado = usuario.id_empleado)
		INNER JOIN sa_claves clave ON (empleado.id_empleado = clave.id_empleado)
	WHERE usuario.id_usuario = %s
		AND clave.clave = %s
		AND clave.modulo = %s;",
		valTpDato($_SESSION['idUsuarioSysGts'], "int"),
		valTpDato(md5($frmPermiso['txtContrasena']), "text"),
		valTpDato($frmPermiso['hddModulo'], "text"));
	$rsPermiso = mysql_query($queryPermiso);
	if (!$rsPermiso) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRowsPermiso = mysql_num_rows($rsPermiso);
	$rowPermiso = mysql_fetch_assoc($rsPermiso);
	
	if ($totalRowsPermiso > 0) {
		if ($frmPermiso['hddModulo'] == "ac_sa_orden_list") {
			$objResponse->script("xajax_devolverOrden('".$frmPermiso['hddIdOrden']."', xajax.getFormValues('frmListaOrdenServicio'));");
		}
		$objResponse->script("byId('imgCerrarDivFlotante1').click();");
	} else {
		$objResponse->alert("Permiso No Autorizado");
		$objResponse->script("byId('imgCerrarDivFlotante1').click();");
	}
	
	return $objResponse;
}

$xajax->register(XAJAX_FUNCTION,"buscar");
$xajax->register(XAJAX_FUNCTION,"cargarPagina");
$xajax->register(XAJAX_FUNCTION,"devolverOrden");
$xajax->register(XAJAX_FUNCTION,"devolverPedido");
$xajax->register(XAJAX_FUNCTION,"imprimirPedidoVenta");
$xajax->register(XAJAX_FUNCTION,"imprimirOrdenServicio");
$xajax->register(XAJAX_FUNCTION,"listaOrdenServicio");
$xajax->register(XAJAX_FUNCTION,"listaPedidoVenta");
$xajax->register(XAJAX_FUNCTION,"validarAperturaCaja");
$xajax->register(XAJAX_FUNCTION,"validarPermiso");
?>