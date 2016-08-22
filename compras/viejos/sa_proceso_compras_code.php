<?php
	require_once("../control/main_control.inc.php");
	
	function aprobar_solicitud($id_solicitud){
		$r= getResponse();
		$c= new connection();
		$c->open();
		$rec=$c->ga_solicitud_compra->doSelect($c,new criteria(sqlEQUAL,$c->ga_solicitud_compra->id_solicitud_compra,$id_solicitud));
		if($rec->id_estado_solicitud_compras == 4
		|| $rec->id_estado_solicitud_compras == 5
		|| $rec->id_estado_solicitud_compras == 7){
			$r->alert('Solicitud Culminada');
		}else{
			//$r->alert('entra en el else '.$estado. '<-');
			$estado=$rec->id_estado_solicitud_compras;
			$r->script('open_win();');
			$r->assign('id_solicitud_compra_f','value',$id_solicitud);
			
			$r->assign('estado_f','value',$estado);
			$display="none";
			switch ($estado){
				case 0:
					$nombre_estado="Enviar";
					//$estado_solicitud="NO Enviada";
					break;
				case 1:
					$nombre_estado="Aprobar";
					//$estado_solicitud="En espera de Aprobacion";
					break;
				case 2:
					$nombre_estado="Conformar";
					//$estado_solicitud="APROBADA - En espera de Conformación";
					break;
				case 3:
					$nombre_estado="Procesar";
					$display="";
					//$estado_solicitud="CONFORMADA - En espera de Proceso";
					break;
				case 6:
					$nombre_estado="Condicionada";
					$display="";
					//$estado_solicitud="EN ORDEN DE COMPRA";
					break;
				default:
					$nombre_estado="";
					//$estado_solicitud="CULMINADA";
					break;
			}
			$r->script("document.getElementById('condicionar_c').style.display='".$display."';");
			$r->assign('tdFlotanteTitulo',inner,$nombre_estado.' Solicitud');
			$r->script("document.getElementById('codigo_empleado').focus();");
		}
		$c->close();
		return $r;
	}
	
	function guardar($form) {
		$r = getResponse();
			//$r->script('_alert("si no esta culminada entra en guardar");');
		@session_start();
		
		$id_solicitud_compra = $form['id_solicitud_compra_f'];
		$estado = $form['estado_f'];
		$codigo_empleado = strtoupper($form['codigo_empleado']);
		$nuevo_estado = $estado + 1;
		//$r->alert(utf_export($form));
		if($codigo_empleado==""){
			$r->script('_alert("Especifique el c&oacute;digo del empleado");');
			return $r;
		}
		if($estado == 3 || $estado == 6){
			//valida los ingresos
			$cambiarestado = $form['cambiarestado'];
			if($cambiarestado==""){
				$r->script('_alert("Especifique la acci&oacute;n a realizar");');
				return $r;
			}
			$motivo_condicionamiento = $form['motivo_condicionamiento'];
			if($motivo_condicionamiento=="" && $cambiarestado!=4){
				$r->script('_alert("Especifique el motivo");');
				return $r;
			}
			$campo = "id_empleado_condicionamiento";
			$campof = "fecha_empleado_condicionamiento";
			$nuevo_estado = $cambiarestado;
			if ($nuevo_estado == 6) {
				if (!xvalidaAcceso($r,"ga_solicitud_compra_list_condicionar")) {
					return $r;
				}
				$mensaje = "Condicionada";
			} else if ($nuevo_estado == 7) {
				if(!xvalidaAcceso($r,"ga_solicitud_compra_list_rechazar")) {
					return $r;
				}
				$mensaje = "Rechazada";
			}
		}
//$r->alert($nuevo_estado);
		switch ($nuevo_estado){
		case 1:
			$campo="id_empleado_solicitud";
			$campof="fecha_empleado_solicitud";
			$mensaje="Enviada";
			break;
			//caso 1 automatico ya no se cumple
		case 2:
			if(!xvalidaAcceso($r,"ga_solicitud_compra_list_aprobar")){
				return $r;
			}
			$campo="id_empleado_aprobacion";
			$campof="fecha_empleado_aprobacion";
			$mensaje="Aprobada";
			break;
		case 3:
			if(!xvalidaAcceso($r,"ga_solicitud_compra_list_conformar")){
				return $r;
			}
			$campo="id_empleado_conformacion";
			$campof="fecha_empleado_conformacion";
			$mensaje="Conformada";
			break;
		case 4:
			if(!xvalidaAcceso($r,"ga_solicitud_compra_list_procesar")){
				$r->alert('d');
				return $r;
			}
			$campo="id_empleado_proceso";
			$campof="fecha_empleado_proceso";
			$mensaje="Procesada";
			break;
		}
		
		$id_empleadosql = sprintf("SELECT 
			pg_empleado.id_empleado AS id_empleado
		FROM pg_empleado
			INNER JOIN pg_cargo_departamento ON (pg_empleado.id_cargo_departamento = pg_cargo_departamento.id_cargo_departamento)
			INNER JOIN pg_departamento ON (pg_cargo_departamento.id_departamento = pg_departamento.id_departamento)
			INNER JOIN pg_empresa ON (pg_departamento.id_empresa = pg_empresa.id_empresa)
		WHERE pg_empleado.codigo_empleado = '%s';",
			$codigo_empleado);
		
		$c = new connection();
		$c->open();
		
		$ret = $c->execute($id_empleadosql);
		$id_empleado = $ret['id_empleado'];
		if($id_empleado == ""){
			$r->alert('No existe el empleado especificado');
			return $r;
		}
		
		$id_empleado_usuariosql = sprintf("SELECT id_empleado FROM pg_usuario WHERE id_usuario = %s;",$_SESSION['idUsuarioSysGts']);
		$ret = $c->execute($id_empleado_usuariosql);
		$id_empleado_usuario = $ret['id_empleado'];
		//$r->alert($id_empleado_usuario);
		
		if($id_empleado_usuario != $id_empleado){
			$r->alert('Debe ingresar al sistema con su clave de usuario.');
			return $r;
		}
		
		$sql = sprintf("UPDATE ga_solicitud_compra SET
			id_estado_solicitud_compras = %s,
			%s = %s,
			%s = CURRENT_DATE(),
			motivo_condicionamiento = '%s'
		WHERE id_solicitud_compra = %s;",
			$nuevo_estado,
			$campo,
			$id_empleado,
			$campof,
			utf8_decode($motivo_condicionamiento),
			$id_solicitud_compra);
		
		$c->begin();
		$result = $c->soQuery($sql);
		if($result === true){
			$r->alert('Solicitud '.$mensaje.' con exito');
			$c->commit();
			$r->script('close_win();');
		} else {
			$c->rollback();
			$r->alert('error: '.$sql);
		}
		
		$c->close();		
		return $r;
	}
	xajaxRegister('aprobar_solicitud');
	xajaxRegister('guardar');
	
	xajaxProcess();
?>