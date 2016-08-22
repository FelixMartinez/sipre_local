<?php //error_reporting(E_ALL);

session_start();
require("../connections/conex.php");

define('PAGE_PRIV','sa_mantenimiento_paquete');//nuevo gregor
//define('PAGE_PRIV','sa_paquete');//anterior

require("../inc_sesion.php");

if(!(validaAcceso(PAGE_PRIV))) {//sa_orden_servicio_list nuevo gregor //sa_orden anterior
	echo "
	<script>
		alert('Acceso Denegado');
		window.location.href = 'index.php';
	</script>";
}

require("control/main_control.inc.php");
require("control/funciones.inc.php");

require("controladores/ac_iv_general.php"); //tiene el cargaLstEmpresaFinal
require("controladores/ac_sa_mantenimiento_paq.php");

function cargar($id, $mode='view'){
	$r=getResponse();
	if (!xvalidaAcceso($r,PAGE_PRIV)){
		return $r;
	}

	$view=array('add'=>'','view'=>'true');

	if($mode=='view'){
		$r->script('$("#edit_window_2 #subtitle").html("Ver");');
	}else{
		$r->script('$("#edit_window_2 #subtitle").html("Editar");');			
	}

	$c=new connection();
	$c->open();

	$q=new query($c);
	$q->add($c->sa_v_paquetes);
	$q->where(new criteria(sqlEQUAL,$c->sa_v_paquetes->id_paquete,$id));

	if($mode!='delete'){
		$rec=$q->doSelect();
		if($rec){
			$r->assign('id_paquete','value',$rec->id_paquete);
			$r->assign('id_empresa','value',$rec->id_empresa);
			$r->assign('codigo_paquete','value',$rec->codigo_paquete);
			$r->assign('descripcion_paquete','value',$rec->descripcion_paquete);

			$sa_v_paq_repuestos=$c->sa_paquete_repuestos;

			$iv_articulos= new table('iv_articulos','',$c);

			$join= $iv_articulos->join($sa_v_paq_repuestos,$iv_articulos->id_articulo,$sa_v_paq_repuestos->id_articulo);

			$qdet=new query($c);
			$qdet->add($join);
			$qdet->where(new criteria(sqlEQUAL,$sa_v_paq_repuestos->id_paquete,$rec->id_paquete));

			$recdet=$qdet->doSelect();
			if($recdet){
				foreach($recdet as $det){
					$scriptdet.="articulo_add({
								id_articulo:".$det->id_articulo.",
								codigo:'".$det->codigo_articulo."',
								descripcion:'".htmlentities($det->descripcion)."',
								iva:'".tieneIva($det->id_articulo)."',
								cantidad:'"._formato($det->cantidad,0)."',
								precio:'".$det->precio."',
								action:'add',
								id_paq_repuesto:".$det->id_paq_repuesto."
								});";
				}
			}
			$sa_v_paq_tempario=$c->sa_paq_tempario;

			$sa_tempario= new table('sa_tempario','',$c);
			$join= $sa_tempario->join($sa_v_paq_tempario,$sa_tempario->id_tempario,$sa_v_paq_tempario->id_tempario);

			$qdet=new query($c);
			$qdet->add($join);
			$qdet->where(new criteria(sqlEQUAL,$sa_v_paq_tempario->id_paquete,$rec->id_paquete));

			$recdet=$qdet->doSelect();
			if($recdet){
				foreach($recdet as $det){
					$scriptdet.="tempario_add({
								id_tempario:".$det->id_tempario.",
								unidad:'".$det->codigo_tempario."',
								descripcion:'".$det->descripcion_tempario."',
								ut:'".$det->ut."',
								costo:'".$det->costo."',
								action:'add',
								id_paq_tempario:".$det->id_paq_tempario."
								});";
				}
			}

			$sa_v_paq_unidad=$c->sa_paq_unidad;

			$sa_v_unidad_basica= new table('sa_v_unidad_basica','',$c);
			$join= $sa_v_unidad_basica->join($sa_v_paq_unidad,$sa_v_unidad_basica->id_unidad_basica,$sa_v_paq_unidad->id_unidad_basica);

			$qdet=new query($c);
			$qdet->add($join);
			$qdet->where(new criteria(sqlEQUAL,$sa_v_paq_unidad->id_paquete,$rec->id_paquete));

			$recdet=$qdet->doSelect();
			if($recdet){
				foreach($recdet as $det){
					$scriptdet.="unidad_add({
								id_unidad_basica:".$det->id_unidad_basica.",
								unidad:'".$det->nombre_unidad_basica."',
								nom_modelo:'".$det->nom_modelo."',
								unidad_completa:'".$det->unidad_completa."',
								action:'add',
								id_paq_unidad:".$det->id_paq_unidad."
								});";
					}
			}
		}
		$r->script('agregar2(false);');
		$r->script($scriptdet);
		$r->script('$("#edit_window_2 input").attr("readonly","'.$view[$mode].'");
					$("#edit_window_2 select").attr("disabled","'.$view[$mode].'");
					$("#edit_window_2 button").attr("disabled","'.$view[$mode].'");
					');
	}else{
		if (!xvalidaAcceso($r,PAGE_PRIV,eliminar)){
			return $r;
		}

		$c->begin();
		$rec=$c->sa_paquetes->doDelete($c,new criteria(sqlEQUAL,$c->sa_paquetes->id_paquete,$id));

		if($rec===true){
			$c->commit();
			$r->script('_alert("Registro eliminado con &eacute;xito"); ');
			$r->script("$('#btnBuscar').click();");
		}else{
			$r->script('_alert("No se puede eliminar el registro, elimine primero las unidades asociadas o es posible que el mismo ya est&aacute; siendo utilizado");');
		}
	}
	$c->close();
	return $r;
}

function guardar($form){
	
	$r=getResponse();
	
	$r->script('$(".field").removeClass("inputNOTNULL");$(".field").removeClass("inputERROR");');

	$c= new connection();
	$c->open();

	$sa_paquetes = new table("sa_paquetes");
	$sa_paquetes->add(new field('id_paquete','',field::tInt,$form['id_paquete']));
	$sa_paquetes->add(new field('id_empresa','',field::tInt,$form['id_empresa']));
	$sa_paquetes->add(new field('codigo_paquete','',field::tString,$form['codigo_paquete']));
	$sa_paquetes->add(new field('descripcion_paquete','',field::tString,$form['descripcion_paquete']));
	$sa_paquetes->insert('fecha_rev','NOW()',field::tFunction);

	$c->begin();

	$id_paquete=$form['id_paquete'];

	if($form['id_paquete']==''){
		if (!xvalidaAcceso($r,PAGE_PRIV,insertar)){
			$c->rollback();
			return $r;
		}

		$result=$sa_paquetes->doInsert($c,$sa_paquetes->id_paquete);
		$id_paquete=$c->soLastInsertId();
	}else{
		if (!xvalidaAcceso($r,PAGE_PRIV,editar)){
			$c->rollback();
			return $r;
		}
		$result=$sa_paquetes->doUpdate($c,$sa_paquetes->id_paquete);
	}

	if($result===true){
		if(isset($form['id_paq_repuesto'])){
			foreach($form['id_paq_repuesto'] as $k => $v){
				$sql='';
				if($form['action'][$k]=='add'){
					if($form['cantidad'][$k]=='0' || intval($form['cantidad'][$k])<=0){
						$r->script('$("#row'.($k+1).'").addClass("inputNOTNULL");');
						$error=true;
						continue;
					}
					
					
					
					
					if($form['id_paq_repuesto'][$k]==''){
						$sql=sprintf("INSERT INTO sa_paquete_repuestos
										(id_paq_repuesto,id_paquete,id_articulo,cantidad, precio)
										VALUES (NULL , %s, %s, %s, %s);",
										$id_paquete,
										$form['id_articulo'][$k],
										field::getTransformType($form['cantidad'][$k],field::tFloat),
										field::getTransformType($form['precio'][$k],field::tFloat)
									);
									
					} 
					
					else{
						$sql=sprintf("UPDATE sa_paquete_repuestos SET cantidad=%s, precio=%s where id_paq_repuesto=%s;",
										field::getTransformType($form['cantidad'][$k],field::tFloat),
										field::getTransformType($form['precio'][$k],field::tFloat),
										$form['id_paq_repuesto'][$k]
									);
					}
				}else{
					if($form['id_paq_repuesto'][$k]!=''){
						$sql=sprintf("DELETE FROM sa_paquete_repuestos where id_paq_repuesto=%s;",
										$form['id_paq_repuesto'][$k]
									);
					}
				}
				if($sql!='' && !$error){
					$resultd = $c->soQuery($sql);
					if(!$resultd){
						$error=true;
					}
				}
			}
		} 
		
		
		
		
		if(isset($form['id_paq_tempario'])){
			foreach($form['id_paq_tempario'] as $k => $v){
				$sql='';
				if($form['actiont'][$k]=='add'){					
					
					if($form['id_paq_tempario'][$k]==''){
						$sql=sprintf("INSERT INTO sa_paq_tempario(id_paq_tempario,id_paquete,id_tempario, ut, costo) VALUES (NULL , %s, %s, %s, %s);",
										$id_paquete,
										$form['id_tempario'][$k],
										field::getTransformType($form['ut'][$k],field::tFloat),
										field::getTransformType($form['costo'][$k],field::tFloat)
									); 
									
					}
					else{
						$sql=sprintf("UPDATE sa_paq_tempario SET ut=%s, costo=%s where id_paq_tempario=%s;",
										field::getTransformType($form['ut'][$k],field::tFloat),
										field::getTransformType($form['costo'][$k],field::tFloat),
										$form['id_paq_tempario'][$k]
									);
						}
				}else{
					if($form['id_paq_tempario'][$k]!=''){
						$sql=sprintf("DELETE FROM sa_paq_tempario where id_paq_tempario=%s;",
										$form['id_paq_tempario'][$k]
									);
					}
				}
				if($sql!='' && !$error){
					$resultd = $c->soQuery($sql);
					
					if(!$resultd){
						$error=true;
					}
				}
			}
		}
		if(isset($form['id_paq_unidad'])){
			foreach($form['id_paq_unidad'] as $k => $v){
				$sql='';
				if($form['actionm'][$k]=='add'){
					if($form['id_paq_unidad'][$k]==''){
						$sql=sprintf("INSERT INTO sa_paq_unidad(id_paq_unidad,id_paquete,id_unidad_basica) VALUES (NULL , %s, %s);",
										$id_paquete,
										$form['id_unidad_basica'][$k]
									);
					}
				}else{
					if($form['id_paq_unidad'][$k]!=''){
						$sql=sprintf("DELETE FROM sa_paq_unidad where id_paq_unidad=%s;",
										$form['id_paq_unidad'][$k]
									);
					}
				}
				if($sql!='' && !$error){
					$resultd = $c->soQuery($sql);
					
					if(!$resultd){
						$error=true;
					}
				}
			}
		}
		if(!$error){
			$r->alert('Guardado con exito');
			$r->script("$('#btnBuscar').click();");
			$r->script("byId('edit_window_2').style.display = 'none'");
			$c->commit();
		}else{
			$r->alert('Verifique los datos ingresados: Rojo: Requerido / Azul: Datos Incorrecto');
		}
	}else{
		$c->rollback();
		foreach ($result as $ex){
			if($ex->type==errorMessage::errorNOTNULL){
				$r->script('$("#field_'.$ex->getObject()->getName().'").addClass("inputNOTNULL");');
			}elseif($ex->type==errorMessage::errorType){
				$r->script('$("#field_'.$ex->getObject()->getName().'").addClass("inputERROR");');
			}else{
				if($ex->numero==connection::errorUnikeKey){
					$r->script('_alert("duplicado");');
					return $r;
				}
			}
		}
		$r->alert('Verifique los datos ingresados: Rojo: Requerido / Azul: Datos Incorrecto');
	}
	$c->close();
	return $r;
}

function agregar_articulo($id,$form,$add=true){
	$r=getResponse();

	$c= new connection();
	$c->open();

	$rec= $c->sa_v_articulos->doSelect($c,new criteria(sqlEQUAL,$c->sa_v_articulos->id_articulo,$id));
	//return var_dump($rec->con->tables['sa_v_articulos']->getSelect($c));
	if($rec){
		$existencia=true;
		if(isset($form['id_unidad_basica'])){				
			$reco=$c->iv_articulos_modelos_compatibles->
						doSelect($c, new criteria(sqlEQUAL,$c->iv_articulos_modelos_compatibles->id_articulo,$id));
			if($reco->getNumRows()!=0){
				foreach($reco as $reg){
					$unidades[]=$reg->id_unidad_basica;
				}
				
				foreach($form['id_unidad_basica'] as $k => $v){
					
					$exits=true;
					if($form['actionm'][$k]=='add' ){
						$exits=in_array($v,$unidades);
						if($exits!=true){
							$unidadesIncompatibles[$v] = nombreUnidad($v); 
							break;
						}
					}
				}
				if($exits==false){//si no encontro en los registros
					$existencia=false;
				}
			}else{//si el registro es cero, no existe
				$unidadesIncompatibles[] = "TODOS los modelos, ya que el Artículo no tiene ningun modelo asignado";
				$existencia=false;
			}
		}
		if(!$existencia){
			$unidadesNombre = implode(",",$unidadesIncompatibles);
			$r->alert("Existe un Modelo que no es compatible con el Artículo seleccionado: \n ".$unidadesNombre."");
			
		}else{
			if($add===true){
				$r->script("articulo_add({
							id_articulo:".$rec->id_articulo.",
							codigo:'".$rec->codigo_articulo."',
							descripcion:'".htmlentities($rec->descripcion)."',
							cantidad:'',
							iva:'".tieneIva($rec->id_articulo)."',
							precio:'".precio($rec->id_articulo)."',
							action:'add',
							id_paq_repuesto:''
							});");
			}else{
				 $r->script("var row= obj('row".$add."');
							row.style.display='';
							var action=obj('action".$add."');
							action.value='add';
							");
			}

		}
	}
	
   
	$c->close();
	
	return $r;
}

function agregar_tempario($id,$form,$add=true){
	//var_dump($form);
	$r=getResponse();        
	$c= new connection();
	$c->open();

	$rec= $c->sa_v_tempario->doSelect($c,new criteria(sqlEQUAL,$c->sa_v_tempario->id_tempario,$id));
	
	
	
	if($rec){
		$existencia=true; 
		if(isset($form['id_unidad_basica'])){
			$reco=$c->sa_tempario_det->doSelect($c, new criteria(sqlEQUAL,$c->sa_tempario_det->id_tempario,$id)); 
			if($reco->getNumRows()!=0){
				foreach($reco as $reg){
					$unidades[]=$reg->id_unidad_basica;
				} 
				foreach($form['id_unidad_basica'] as $k => $v){
					$exits=true; 
					if($form['actionm'][$k]=='add' ){
						$exits=in_array($v,$unidades);
						if($exits!=true){								
							$unidadesIncompatibles[] = nombreUnidad($v);
							break;
						}
					}
				}
				if($exits==false){//si no encontro en los registros
					$existencia=false;
				}
			}else{//si el registro es cero, no existe
				$unidadesIncompatibles[] = "TODOS los modelos, ya que la posicion no tiene ningun modelo asignado";
				$existencia=false;
			}
		} 
		if(!$existencia){
			$unidadesNombre = implode(",",$unidadesIncompatibles);
			$r->alert("Existe un Modelo que no es compatible con la Posición seleccionada: \n ".$unidadesNombre."");
		}else{
			if($add===true){
		
		  $recUni= $c->sa_tempario_det->doSelect($c,new criteria(sqlEQUAL,$c->sa_tempario_det->id_tempario,$id));
				$r->script("tempario_add({
							id_tempario:".$rec->id_tempario.",
							unidad:'".$rec->codigo_tempario."',                                
							descripcion:'".$rec->descripcion_tempario."',
							ut:'".$recUni->ut."',
							costo:'".$rec->costo."',
							action:'add',
							id_paq_tempario:''
							});
							");
			}else{
				$r->script("var row= obj('rowt".$add."');
							row.style.display='';
							var action=obj('actiont".$add."');
							action.value='add';
							");
			}
		}
	}
	$c->close();
	return $r;
}

function agregar_unidad($id,$form,$add=true){
	$r=getResponse();
	$c= new connection();
	$c->open();

	$rec= $c->sa_v_unidad_basica->doSelect($c,new criteria(sqlEQUAL,$c->sa_v_unidad_basica->id_unidad_basica,$id));
	if($rec){
		$existencia=true;
		if(isset($form['id_articulo'])){
			foreach($form['id_articulo'] as $k => $v){
				$exits=true;
				if($form['action'][$k]=='add'){
					$reco=$c->iv_articulos_modelos_compatibles->
									doSelect($c, new criteria(sqlEQUAL,$c->iv_articulos_modelos_compatibles->id_articulo,$v));
					if($reco->getNumRows()!=0){
						$exits=false;
						foreach($reco as $reg){
							$exits=($id==$reg->id_unidad_basica);
							if($exits==true){
								break;
							}
						}
						if($exits==false){  
							$articulosIncompatibles[] = nombreArticulo($v);
							break;
						}
					}
				}
			}
			if($exits==false){
				$existencia=false;
			}
		}
		if(!$existencia){
			$codigoArticulo = implode(",",$articulosIncompatibles);
			$r->alert("Existe un artículo que no es compatible con el Unidad seleccionada, Códigos: \n ".$codigoArticulo."");                
		}
		if(isset($form['id_tempario'])){
			foreach($form['id_tempario'] as $k => $v){
				$exits=true;
				if($form['actiont'][$k]=='add'){
					$reco=$c->sa_tempario_det->doSelect($c, new criteria(sqlEQUAL,$c->sa_tempario_det->id_tempario,$v));
					if($reco->getNumRows()!=0){
						$exits=false;
						foreach($reco as $reg){
							$exits=($id==$reg->id_unidad_basica);
							if($exits==true){
								break;
							}
						}
						if($exits==false){ 
							$posicionIncompatible[] = nombreTempario($v);
							break;
						}
					}
				}
			}
			if($exits==false){
				$existencia=false;
			}
		}
	}
	if(!$existencia){
		$temparioCodigo = implode(",",$posicionIncompatible);
		 $r->alert("Existe una posición que no es compatible con el Unidad seleccionada, Códigos: \n ".$temparioCodigo."");
		
	}else{
		if($add===true){
			$r->script("unidad_add({
						id_unidad_basica:".$rec->id_unidad_basica.",
						unidad:'".$rec->nombre_unidad_basica."',
						nom_modelo:'".$rec->nom_modelo."',
						unidad_completa:'".$rec->unidad_completa."',
						action:'add',
						id_paq_unidad:''
						});
						");
		}else{
			$r->script("var row= obj('rowm".$add."');
						row.style.display='';
						var action=obj('actionm".$add."');
						action.value='add';
						");
		}
	}

	$c->close();
	return $r;
}

    xajaxRegister('guardar');
    xajaxRegister('cargar_listas');
    xajaxRegister('cargar');
    xajaxRegister('agregar_articulo');
    xajaxRegister('agregar_tempario');
    xajaxRegister('agregar_unidad');

    xajaxProcess();

    includeDoctype();
    $c= new connection();
    $c->open();

    $empresas=getEmpresaList($c,false);

    $articulo= $c->sa_v_articulos;

    $articulos= $articulo->doSelect($c)->getAssoc('id_articulo','articulo_completo');
    $temparios= $c->sa_v_tempario->doSelect($c)->getAssoc('id_tempario','tempario_completo');
    $unidades= $c->sa_v_unidad_basica->doSelect($c)->getAssoc('id_unidad_basica','nombre_unidad_basica');

    $c->close();
?>

<html>
<head>


<?php
	includeMeta();
	includeScripts();
	getXajaxJavascript();
?>
<link rel="stylesheet" type="text/css" href="../style/styleRafk.css"/>
<link rel="stylesheet" type="text/css" href="css/sa_general.css" />
<link rel="stylesheet" type="text/css" href="../js/domDragServicios.css"/>

<title>.: SIPRE <?php echo cVERSION; ?> :. Servicios - Paquetes</title>        
<link rel="icon" type="image/png" href="../img/login/icono_sipre_png.png" />
    
<style type="text/css">
	button img{
		padding-right:1px;
		padding-left:1px;
		padding-bottom:1px;
		vertical-align:middle;
	}
	.order_table tbody tr:hover,.order_table tbody tr.impar{
		cursor:default;
	}
	.order_table tbody tr:hover img,.order_table tbody tr.impar img{
		cursor:pointer;
	}
	#table_articulos td{
		border: 1px solid #ccc;
	}
	#table_articulos thead td{
		background:#BFBFBF;
	}
	.impar{
		background:#DFDFDF;
	}
	
</style>
<script type="text/javascript">
	
	window.onbeforeunload=function(){
		if(byId('edit_window_2').style.display == ''){
			return "";//usa cualquier return, no sive los msj
		}
	}
	
	var counter_articulo=0;
	var tabla_articulo=new Array();

	function agregar_articulo(valor){
		if(valor==0){
			_alert('Elija un art&iacute;culo de la lista para agregar')
			return;
		}
		for(var i=1; i<=counter_articulo;i++){
			var ob= obj('id_articulo'+i);
			if(ob.value==valor){
				var row= obj('row'+i);
				if(row.style.display=='none'){
					if(_confirm('El Art&iacute;culo fue anteriormente eliminado, &iquest;Desea agregarlo de nuevo?')){
						xajax_agregar_articulo(valor,xajax.getFormValues('formulario2'));
						//xajax_agregar_articulo(obj('id_articulo'+i).value,xajax.getFormValues('formulario2'),i);
					}
				}else{
					_alert('Ya existe el Art&iacute;culo');
					//_confirm('Ya existe el Art&iacute;culo &iquest;Desea agregarlo?');
					//xajax_agregar_articulo(valor,xajax.getFormValues('formulario2'));
				}
				return;
			}
		}
		xajax_agregar_articulo(valor,xajax.getFormValues('formulario2'));
	}

	function articulo_add(data){
		var tabla=obj('tbody_articulos');
		var nt = new tableRow("tbody_articulos");
		tabla_articulo[counter_articulo]=nt;
		counter_articulo++;
		nt.setAttribute('id','row'+counter_articulo);
		if(counter_articulo%2){
			nt.$.className='';
		}else{
			nt.$.className='impar';
		}
		//nt.$.className='field';
		var c1= nt.addCell();
			c1.$.innerHTML=data.codigo;
		var c2= nt.addCell();
			c2.$.innerHTML=data.descripcion;
		var impuesto= nt.addCell();
			impuesto.$.innerHTML = "<center>"+data.iva+"</center>";
		var c3= nt.addCell();
			c3.$.innerHTML='<center><input type="text" id="cantidad'+counter_articulo+'" name="cantidad[]" value="'+data.cantidad+'" onchange="set_toNumber(this,0);" onkeypress="return inputInt(event);" style="width:40px;" /></center><input type="hidden" id="id_articulo'+counter_articulo+'" name="id_articulo[]" value="'+data.id_articulo+'" /><input type="hidden" id="id_paq_repuesto'+counter_articulo+'" name="id_paq_repuesto[]" value="'+data.id_paq_repuesto+'" /><input type="hidden" id="action'+counter_articulo+'" name="action[]" value="'+data.action+'" />';
			
		var c3= nt.addCell();
			c3.$.innerHTML='<center><input type="text" id="precio'+counter_articulo+'" name="precio[]" value="'+data.precio+'"  style="width:70px;" /></center>';
			
		var c4= nt.addCell();
			c4.$.innerHTML='<center><img src="<?php echo getUrl('img/iconos/delete.png'); ?>" border="0" alt="Quitar" style="cursor:pointer;" onclick="articulo_quit('+counter_articulo+')"/></center>';
	}

	function articulo_quit(cont){
		if(_confirm("&iquest;Desea eliminar el Art&iacute;culo de la Lista?")){
			var fila=obj('row'+cont);
			fila.style.display='none';
			var action=obj('action'+cont);
			action.value='delete';
		}
	}

	function articulo_vaciar(){
		var tabla=obj('tbody_articulos');
		for(var t in tabla_articulo){
			tabla.removeChild(tabla_articulo[t].$);
		}
		counter_articulo=0;
		tabla_articulo=new Array();
	}

	var counter_tempario=0;
	var tabla_tempario=new Array();

	function agregar_tempario(valor){
		if(valor==0){
			_alert('Elija una Posici&oacute;n de Trabajo de la lista para agregar')
			return;
		}
		for (var i=1; i<=counter_tempario;i++){
			var ob= obj('id_tempario'+i);
			if(ob.value==valor){
				var row= obj('rowt'+i);
				if(row.style.display=='none'){
					if(_confirm('La Posici&oacute;n de Trabajo fue anteriormente eliminada, &iquest;Desea agregarla de nuevo?')){
					   
						xajax_agregar_tempario(obj('id_tempario'+i).value,xajax.getFormValues('formulario2'),i);
					}
				}else{
					_alert('Ya existe la Posici&oacute;n de Trabajo');
				}
				return;
			}
		}
				   
		xajax_agregar_tempario(valor,xajax.getFormValues('formulario2'));
	}

	function tempario_add(data){
		var tabla=obj('tbody_tempario');
		var nt = new tableRow("tbody_tempario");
		tabla_tempario[counter_tempario]=nt;
		counter_tempario++;
		nt.setAttribute('id','rowt'+counter_tempario);
		nt.$.className='field';
		var c1= nt.addCell();
		c1.$.innerHTML=data.unidad;
		var c2= nt.addCell();
		c2.$.innerHTML=data.descripcion;
	   
		
		var c3= nt.addCell();
			c3.$.innerHTML='<center><input type="text" id="ut'+counter_tempario+'" name="ut[]" value="'+data.ut+'" onchange="set_toNumber(this,0);" onkeypress="return inputInt(event);" style="width:40px;" /></center>';
			
			
		var c3= nt.addCell();
			c3.$.innerHTML='<center><input type="text" id="costo'+counter_tempario+'" name="costo[]" value="'+data.costo+'"  style="width:70px;" /></center>';
			
		var c3= nt.addCell();
		c3.$.innerHTML='<center><img src="<?php echo getUrl('img/iconos/delete.png'); ?>" border="0" style="cursor:pointer;" alt="Quitar" onclick="tempario_quit('+counter_tempario+')"></center><input type="hidden" id="id_tempario'+counter_tempario+'" name="id_tempario[]" value="'+data.id_tempario+'" /><input type="hidden" id="id_paq_tempario'+counter_tempario+'" name="id_paq_tempario[]" value="'+data.id_paq_tempario+'" /><input type="hidden" id="actiont'+counter_tempario+'" name="actiont[]" value="'+data.action+'" />';
	}

	function tempario_quit(cont){
		if(_confirm("&iquest;Desea eliminar la Posici&oacute;n de Trabajo de la Lista?")){
			var fila=obj('rowt'+cont);
			fila.style.display='none';
			var action=obj('actiont'+cont);
			action.value='delete';
		}
	}

	function tempario_vaciar(){
		var tabla=obj('tbody_tempario');
		for(var t in tabla_tempario){
			tabla.removeChild(tabla_tempario[t].$);
		}
		counter_tempario=0;
		tabla_tempario=new Array();
	}

	var counter_modelo=0;
	var tabla_modelo=new Array();

	function agregar_unidad(valor){
		if(valor==0){
			_alert('Elija un Modelo de la lista para agregar')
			return;
		}
		for(var i=1; i<=counter_modelo;i++){
			var ob= obj('id_unidad_basica'+i);
			if(ob.value==valor){
				var row= obj('rowm'+i);
				if(row.style.display=='none'){
					if(_confirm('El Modelo fue anteriormente eliminado, &iquest;Desea agregarlo de nuevo?')){
						xajax_agregar_unidad(obj('id_unidad_basica'+i).value,xajax.getFormValues('formulario2'),i);
					}
				}else{
					_alert('Ya existe el Modelo');
				}
				return;
			}
		}
		xajax_agregar_unidad(valor,xajax.getFormValues('formulario2'));
	}

	function unidad_add(data){
		var tabla=obj('tbody_modelo');
		var nt = new tableRow("tbody_modelo");
		tabla_modelo[counter_modelo]=nt;
		counter_modelo++;
		nt.setAttribute('id','rowm'+counter_modelo);
		if(counter_modelo%2){
			nt.$.className='';
		}else{
			nt.$.className='impar';
		}

		//nt.$.className='field';
		var c1= nt.addCell();
		c1.$.innerHTML=data.unidad;
		var c2= nt.addCell();
		c2.$.innerHTML=data.nom_modelo;
		var c3= nt.addCell();
		c3.$.innerHTML=data.unidad_completa;
		var c4= nt.addCell();
		c4.$.innerHTML='<center><img src="<?php echo getUrl('img/iconos/delete.png'); ?>" border="0" style="cursor:pointer;" alt="Quitar" onclick="unidad_quit('+counter_modelo+')"/></center><input type="hidden" id="id_unidad_basica'+counter_modelo+'" name="id_unidad_basica[]" value="'+data.id_unidad_basica+'" /><input type="hidden" id="id_paq_unidad'+counter_modelo+'" name="id_paq_unidad[]" value="'+data.id_paq_unidad+'" /><input type="hidden" id="actionm'+counter_modelo+'" name="actionm[]" value="'+data.action+'" />';
	}

	function unidad_quit(cont){
		if(_confirm("&iquest;Desea eliminar el Modelo de la Lista?")){
			var fila=obj('rowm'+cont);
			fila.style.display='none';
			var action=obj('actionm'+cont);
			action.value='delete';
		}
	}

	function unidad_vaciar(){
		var tabla=obj('tbody_modelo');
		for(var t in tabla_modelo){
			tabla.removeChild(tabla_modelo[t].$);
		}
		counter_modelo=0;
		tabla_modelo=new Array();
	}

	var datos = {
		fecha: 'null',
		date:new Date(),
		page:0,
		maxrows:15,
		order:null,
		ordertype:null,
		busca:''
	}	

	function actualizarRpto(add,paq){
		byId('tipo_precio_window').style.display = '';
		centrarDiv(byId('tipo_precio_window'));
		xajax_comboPreciosRpto(paq);   
	}

	function agregar2(add){
		if(byId('edit_window_2').style.display == 'none'){
			byId('edit_window_2').style.display = '';
			centrarDiv(byId('edit_window_2'));
		}
		if(add){
			$('#edit_window_2 input:not(#cancelar)').val('');//TODOS LOS INPUTS, ASI LIMPIA HIDDEN TAMBIEN
			//$('#edit_window_2 select').val('');//Este ponia la empresa 1
			$('#edit_window_2 #capa_id_art_inventario').html('');
			$("#edit_window_2 #subtitle").html("Agregar");
			$("#edit_window_2 input").not("#fecha_venta").attr("readonly","");
			$("#edit_window_2 select").attr("disabled",""); //el segundo parametro estaba vacio ""
			$("#edit_window_2 button").attr("disabled","");
			$("#info_cliente").html("");
		}
		limpiar_select();//borra el resto de las empresas
		
		articulo_vaciar();
		unidad_vaciar();
		tempario_vaciar();
		$(".field").removeClass("inputNOTNULL");$(".field").removeClass("inputERROR");
	}
			
	function windowUnidad(){		
		xajax_buscarUnidad(xajax.getFormValues('frmBusUnidad'));
	}
	
	function windowArt(){
		if(counter_modelo == 0){
			alert("Debe elegir por lo menos una unidad");
			return false;
		}
		xajax_cargaLstBusq();
		xajax_buscarArticulo(xajax.getFormValues('frmBusArt'), xajax.getFormValues('formulario2'));
	}
	
	function windowMo(){
		if(counter_modelo == 0){
			alert("Debe elegir por lo menos una unidad");
			return false;
		}
		xajax_cargaLstBusqMo();
		xajax_buscarTempario(xajax.getFormValues('frmBusMo'), xajax.getFormValues('formulario2'));
		
	}
			
	function letrasNumerosEspeciales(e) {
		tecla = (document.all) ? e.keyCode : e.which;
		if (tecla == 0 || tecla == 8)
			return true;
		patron = /[-,.()0-9A-Za-z\s ]/;
		te = String.fromCharCode(tecla);
		return patron.test(te);
	}

</script>
</head>

<body class="bodyVehiculos" style="font-size: 11px;">
<div id="divGeneralPorcentaje">
	<div class="noprint">
	<?php include("banner_servicios.php"); ?>
    </div>
    
	<div id="divInfo" class="print">
            <table align="center" border="0" width="100%">
            <tr>
				<td align="right" class="titulo_pagina">
                	<span>Mantenimiento de Paquetes</span>
                </td>
			</tr>
            </table>
            
            <br />
            <div id="principal">
                <div>
                <form id="frmBuscar" name="frmBuscar" onsubmit="return false;">
                <table width="100%">
                	<tr>
                    	<td width="" align="left" style="white-space:nowrap">
                        <button type="button" value="Nuevo 2" onClick="agregar2(true);">
                            <img border="0" src="<?php echo getUrl('img/iconos/plus.png') ?>" alt="plus"/>Nuevo
                        </button>
                        <input type="text" id="txtCriterio" name="txtCriterio" onkeyup="$('#btnBuscar').click();" />
                        <button type="button" id="btnBuscar" title="Buscar" onClick="xajax_buscarPaquete(xajax.getFormValues('frmBuscar'));" >
                            <img border="0" src="<?php echo getUrl('img/iconos/find.png') ?>" alt="find"/>
                        </button>
                        <button type="button" value="reset" title="Restablecer" onClick="$('#txtCriterio').val(''); $('#btnBuscar').click();" >
                            <img border="0" src="<?php echo getUrl('img/iconos/cc.png') ?>" alt="cc"/>
                        </button>
                        </td>                        
                        <td align="left">
                        
                        	<table width="100%">
                                <tr>
                                    <td>
                                        <table width="100%" cellspacing="0" cellpadding="0" class="divMsjInfo2">
                                        <tr>
                                            <td width="25"><img width="25" src="../img/iconos/ico_info.gif"></td>
                                            <td align="center">
                                                <table border="0">
                                                <tr align="left">
                                                    <td style="white-space:nowrap">
													<?php 
														if(configPaqueteCombo($_SESSION['idEmpresaUsuarioSysGts']) == "1"){
															echo "Modo Combo: El precio establecido en esta secci&oacute;n, es el precio definitivo.";
														}else{
															echo "Modo Paquete: El precio del paquete se determina con el precio actual, NO en esta secci&oacute;n.";
														}
													?>
													</td>							
                                                </tr>
                                                </table>
                                            </td>
                                        </tr>
                                        </table>
                                    </td>
                                </tr>
                        	</table>
							
                        </td>
                        <td width="" align="right" style="white-space:nowrap">
                            <button type="button" value="reset" title="Actualizar" onClick="if(_confirm('Desea Actualizar Repuestos?')) actualizarRpto(true,0);" >
                                <img border="0" src="<?php echo getUrl('img/iconos/cc.png') ?>"/>Actualizar Todos Los Repuestos
                            </button>
                            <button type="button" value="reset" title="Actualizar" onClick="if(_confirm('Desea Actualizar Mano De Obra?')) xajax_actualizar_mo('0');" >
                                <img border="0" src="<?php echo getUrl('img/iconos/cc.png') ?>"/>Actualizar Todas Las MO
                            </button>
                        </td>
                    </tr>
                </table> 
                </form>
                </div>
                <div id='divListaPaquetes'></div>
            </div>
            
            <div>
            	<table width="100%">
                	<tr class="noprint">
                        <td>
                            <table width="100%" cellspacing="0" cellpadding="0" class="divMsjInfo2">
                            <tbody><tr>
                                <td width="25"><img width="25" src="../img/iconos/ico_info.gif"></td>
                                <td align="center">
                                    <table border="0">
                                    <tbody><tr align="left">
                                        <td id="tdImgAccionVerOrden"><img src="../img/iconos/ico_view.png"></td>
                                        <td id="tdDescripAccionVerOrden">Ver Paquete</td>
                                        <td>&nbsp;</td>
                                        <td id="tdImgAccionEdicionOrden"><img src="../img/iconos/ico_edit.png"></td>
                                        <td id="tdDescripAccionEdicionOrden">Editar Paquete</td>
                                        <td>&nbsp;</td>
                                        <td><img src="../img/iconos/delete.png"></td>
                                        <td>Eliminar Paquete</td>
                                        <td>&nbsp;</td>
                                        <td><img src="../img/iconos/ico_print.png"></td>
                                        <td>Imprimir Orden</td>
                                        <td>&nbsp;</td>
                                        <td>
                                        	<img width="14" border="0" style="position:absolute; margin-left:-4px; margin-top:6px;" src="../img/iconos/cc.png">
                                            <img border="0" src="../img/iconos/package.png">
										</td>
                                        <td>Actualizar Repuestos</td>
                                        <td>&nbsp;</td>
                                        <td>
                                        	<img width="14" border="0" style="position:absolute; margin-left:-4px; margin-top:6px;" src="../img/iconos/cc.png">
                                            <img border="0" src="../img/iconos/diagnostico.png">
										</td>
                                        <td>Actualizar Manos de Obra</td>
                                    </tr>
                                    </tbody></table>
                                </td>
                            </tr>
                            </tbody></table>
                        </td>
                    </tr>
                </table>
            </div>

            <div class="window" id="unidad_window" style="min-width:210px;display:none;">
                <div class="title" id="title_unidad_window">
                    Agregar Articulo
                </div>
                <div class="content">
                    Seleccione una unidad de la lista para agregar:
                    <div id="lista_unidades"></div>
                </div>
                <img class="close_window" src="<?php echo getUrl('img/iconos/close_dialog.png'); ?>" alt="cerrar" title="Cerrar" style="cursor:pointer;" onClick="byId('unidad_window').style.display = 'none';" border="0" />
            </div>     
    </div>
    
    <div class="noprint">
    <?php include("pie_pagina.php"); ?>
    </div>
</div>
</body>
</html>


<div class="window" id="edit_window_2" style="min-width:510px;display:none;">
<div class="title" id="title_window_2">
    Paquetes de servicio
</div>
<div class="content">
    <form id="formulario2" name="formulario2" onSubmit="return false;" style="margin:0px;padding:0px;" action="#">
        <input type="hidden" id="id_paquete" name="id_paquete" />
        <table>
            <tr>
                <td valign="top">
                    <table class="insert_table" style="width:auto;">
                        <tbody>
                            <tr>
                                <td colspan="6">&nbsp;</td>
                            </tr>
                            <tr>
                                <td class="label">Empresa</td>
                                <td class="field" id="field_id_empresa"> SEGUNDO
                                    <?php
                                       // echo inputSelect('id_empresa',$empresas);
                                    ?>
                                </td>

                                <td class="label">C&oacute;digo</td>
                                <td class="field" id="field_codigo_paquete">
                                    <input type="text" name="codigo_paquete" id="codigo_paquete" onKeyPress="return letrasNumerosEspeciales(event);"/>
                                </td>

                                <td class="label">Descripci&oacute;n</td>
                                <td class="field" id="field_descripcion_paquete">
                                    <input type="text" name="descripcion_paquete" id="descripcion_paquete" onKeyPress="return letrasNumerosEspeciales(event);"/>
                                </td>
                            </tr>
                            <tr>
                                <td colspan="6">
                                    <div>
                                        Para agregar At&iacute;culos, unidades y Posiciones de Trabajo, seleccionelas de sus respectivas listas y haga click en el bot&oacute;n (+) situado al lado de la lista.
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td colspan="6">&nbsp;</td>
                            </tr>
                        </tbody>
                    </table>
                </td>
            </tr>
            <tr>
                <td colspan="6">
                    <table width="100%" border="0" cellpadding="0" >
                        <tr>
                            <td colspan="9">
                                <table width="100%" border="0" cellpadding="0" cellspacing="0" class="tituloArea" style="border:0">
                                    <tr>
                                        <td width="44%" height="22" align="left">
                                            <button type="button" id="btnInsertarPaq" name="btnInsertarPaq" onClick="windowUnidad();" title="Agregar Paquete">
                                                <img src="../img/iconos/ico_agregar.gif" alt="ico_agregar"/>
                                            </button>
                                        </td>
                                        <td width="56%" align="left">UNIDADES</td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                        <tr class="tituloColumna">
                            <td width="20%" class="celda_punteada">Nombre</td>
                            <td width="30%" class="celda_punteada">Modelo</td>
                            <td width="42%" class="celda_punteada">Descripci&oacute;n</td>
                            <td width="8%" class="celda_punteada">Acciones</td>
                        </tr>
                        <tbody id="tbody_modelo"></tbody>
                    </table>
                </td>
            </tr>
            
            <tr>
                <td colspan="6">&nbsp;</td>
            </tr>
            <tr>
                <td colspan="6">
                    <table width="100%" border="0" cellpadding="0" >
                        <tr>
                            <td colspan="9">
                                <table width="100%" border="0" cellpadding="0" cellspacing="0" class="tituloArea" style="border:0">
                                    <tr>
                                        <td width="44%" height="22" align="left">
                                            <button type="button" id="btnInsertarPaq" name="btnInsertarPaq" onClick="windowArt();" title="">
                                                <img src="../img/iconos/ico_agregar.gif" alt="ico_agregar"/>
                                            </button>
                                        </td>
                                        <td width="56%" align="left">ART&Iacute;CULOS</td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                        <tr class="tituloColumna">
                            <td width="15%" class="celda_punteada">C&oacute;digo</td>
                            <td width="55%" class="celda_punteada">Descripci&oacute;n</td>
                            <td width="8%" class="celda_punteada">Impuesto</td>
                            <td width="8%" class="celda_punteada">Cantidad</td>
                            <td width="14%" class="celda_punteada">Precio</td>
                            <td width="8%" class="celda_punteada">Acciones</td>
                        </tr>
                        <tbody id="tbody_articulos"></tbody>
                    </table>
                </td>
            </tr>
            
            <tr>
                <td colspan="6">&nbsp;</td>
            </tr>
            <tr>
                <td colspan="6">
                    <table width="100%" border="0" cellpadding="0" >
                        <tr>
                            <td colspan="9">
                                <table width="100%" border="0" cellpadding="0" cellspacing="0" class="tituloArea" style="border:0">
                                    <tr>
                                        <td width="40%" height="22" align="left">
                                            <button type="button" id="btnInsertarPaq" name="btnInsertarPaq" onClick="windowMo();" title="">
                                                <img src="../img/iconos/ico_agregar.gif" alt="ico_agregar"/>
                                            </button>
                                        </td>
                                        <td width="60%" align="left">POSICIONES DE TRABAJO</td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                        <tr class="tituloColumna">
                            <td width="20%" class="celda_punteada">C&oacute;digo</td>
                            <td width="50%" class="celda_punteada">Tempario</td>
                            <td width="10%" class="celda_punteada">UT</td>
                            <td width="10%" class="celda_punteada">Precio/Costo</td><!-- cuando es combo el costo y precio se guardan igual al precio. Cuando se actualiza usa el precio de la mano de obra original -->
                            <td width="10%" class="celda_punteada">Acciones</td>
                        </tr>
                        <tbody id="tbody_tempario" ></tbody>
                    </table>
                </td>
            </tr>
            
            <tr>
                <td colspan="6">&nbsp;</td>
            </tr>

            <tr>
                <td colspan="2">
                    <table style="width:100%;" >
                        <tbody>
                            <tr>
                                <td nowrap="nowrap">
                                    <div class="leyend">
                                        <span class="inputNOTNULL"></span> Valor Requerido
                                    </div>
                                </td>
                                <td>
                                    <div class="leyend">
                                        <span class="inputERROR"></span> Valor Incorrecto
                                    </div>
                                </td>
                                <td  align="right">
                                    <button type="button" id="guardar" onClick="xajax_guardar(xajax.getFormValues('formulario2'));" ><img border="0" src="<?php echo getUrl('img/iconos/save.png'); ?>" alt="save"/>Guardar</button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </td>
            </tr>
<!--			<tr>
                <td align="right">
                    <hr>
                    <input type="button" id="cancelar" value="Cancelar" onclick="byId('edit_window_2').style.display = 'none';">
                </td>
            </tr>-->
        </table>
    </form>
</div>
<img class="close_window" src="<?php echo getUrl('img/iconos/close_dialog.png'); ?>" alt="cerrar" title="Cerrar" style="cursor:pointer;" onClick="byId('edit_window_2').style.display = 'none';" border="0" />
</div>


<div class="window" id="bus_unidad" style="min-width:710px;display:none;">
<div class="title" id="title_window_unidad">
    Listado de Unidades
</div>
<div class="content">
    <form id="frmBusUnidad"  name="frmBusUnidad" onSubmit="return false;" action="#">
        <table>
            <tr>
                <td>
                	<table align="right">
                        <tr>
                        	<td width="150" align="right" class="tituloCampo">
                                Código / Descripción:
                            </td>
                            <td>
                                <input type="text" name="bus_unidad" id="bus_unidad" onKeyUp="xajax_buscarUnidad(xajax.getFormValues('frmBusUnidad'));">
                            </td>
                            <td>
                                <input type="button" value="Buscar" id="btnBuscarUni" name="btnBuscarUni" onClick="xajax_buscarUnidad(xajax.getFormValues('frmBusUnidad'));">
                            </td>
                            <td>
                                <input type="button" value="Ver Todos" onClick="xajax_buscarUnidad(xajax.getFormValues('frmBusUnidad'));">
                            </td>
                        </tr>
                    </table>
                </td>                
            </tr>
            <tr>
                <td>
                    <div id="divUnidad"></div>
                </td>
            </tr>
            <tr>
                <td align="right">
                    <hr>
                    <input type="button" value="Cancelar" onClick="byId('bus_unidad').style.display = 'none';">
                </td>
            </tr>
        </table>
    </form>
</div>
<img class="close_window" src="<?php echo getUrl('img/iconos/close_dialog.png'); ?>" alt="cerrar" title="Cerrar" style="cursor:pointer;" onClick="byId('bus_unidad').style.display = 'none';" border="0" />
</div>


<div class="window" id="bus_art" style="min-width:1000px;display:none;">
<div class="title" id="title_window_art">
    Listado de Art&iacute;culo
</div>
<div class="content">
    <form id="frmBusArt" name="frmBusArt" onSubmit="return false;" action="#">
        <table border="0" id="tblArticulo" width="100%">
            <tr>
                <td>
                    <table border="0" width="100%">
                        <tr align="left">
                            <td align="right" class="tituloCampo" width="8%">Marca:</td>
                            <td id="tdlstMarcaBusq" width="24%">
                                <select id="lstMarcaBusq" name="lstMarcaBusq">
                                    <option value="-1">Todos...</option>
                                </select>
                            </td>
                            <td align="right" class="tituloCampo" width="15%">Tipo de Articulo:</td>
                            <td id="tdlstTipoArticuloBusq" width="24%">
                                <select id="lstTipoArticuloBusq" name="lstTipoArticuloBusq">
                                    <option value="-1">Todos...</option>
                                </select>
                            </td>
                            <td align="right" class="tituloCampo" width="10%">Código:</td>
                            <td id="tdCodigoArt" width="24%">
                                 <input type="text" name="txtCodigoArticulo" id="txtCodigoArticulo" value="" onKeyUp="xajax_buscarArticulo(xajax.getFormValues('frmBusArt'), xajax.getFormValues('formulario2'));" size="20">
                             <!--   <input type="text" name="txtCodigoArticulo1" id="txtCodigoArticulo1" value="" size="8">
                                <input type="text" name="txtCodigoArticulo2" id="txtCodigoArticulo2" value="" size="8">-->
                            </td>
                        </tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo">Secci&oacute;n:</td>
                            <td colspan="3" id="tdlstSeccionBusq">
                                <select id="lstSeccionBusq" name="lstSeccionBusq">
                                    <option value="-1">Todos...</option>
                                </select>
                            </td>
                            <td align="right" class="tituloCampo">Descripci&oacute;n:</td>
                            <td>
                                <input type="text" id="txtDescripcionBusq" name="txtDescripcionBusq" onKeyUp="$('btnBuscarArti').click();" size="30"/>
                            </td>
                        </tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo">Sub-Secci&oacute;n:</td>
                            <td colspan="4" id="tdlstSubSeccionBusq">
                                <select id="lstSubSeccionBusq" name="lstSubSeccionBusq">
                                    <option value="-1">Todos...</option>
                                </select>
                            </td>
                            <td align="right">
                                <input type="button" id="btnBuscarArt" name="btnBuscarArt" onClick="xajax_buscarArticulo(xajax.getFormValues('frmBusArt'), xajax.getFormValues('formulario2'));" value="Buscar..."/>
                                <input type="button" onClick="document.forms['frmBusArt'].reset(); xajax_buscarArticulo(xajax.getFormValues('frmBusArt'), xajax.getFormValues('formulario2'));" value="Ver Todo"/>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
            <tr>
                <td id="tdListadoArticulos">
                    <table width="100%">
                        <tr class="tituloColumna">
                            <td>Código</td>
                            <td>Descripción</td>
                            <td>Marca</td>
                            <td>Tipo</td>
                            <td>Sección</td>
                            <td>Sub-Sección</td>
                            <td>Disponible</td>
                            <td>Reservado</td>
                        </tr>
                    </table>
                </td>
            </tr>
            <tr>
                <td align="right">
                    <hr>
                    <input type="button" value="Cancelar" onClick="byId('bus_art').style.display = 'none';">
                </td>
            </tr>
        </table>
    </form>
</div>
<img class="close_window" src="<?php echo getUrl('img/iconos/close_dialog.png'); ?>" alt="cerrar" title="Cerrar" style="cursor:pointer;" onClick="byId('bus_art').style.display = 'none';" border="0" />
</div>


<div class="window" id="bus_mo" style="min-width:940px;display:none;">
<div class="title" id="title_window_mo">
    Listado de Posiciones de Trabajo
</div>
<div class="content">
    <form id="frmBusMo" name="frmBusMo" onSubmit="return false;" action="#">
        <table border="0" id="tblBusquedaTempario" width="100%">
            <tr>
            	<td>
                	<table border="0" align="right">
                        <tr>
                            <td width="100" align="right" class="tituloCampo">Secci&oacute;n:</td>
                            <td id="tdListSeccionTemp">
                                <select id="lstSeccionTemp" name="lstSeccionTemp">
                                    <option value="-1">Seleccione...</option>
                                </select>
                            </td>
                            <td width="100" align="right" class="tituloCampo">Subsecci&oacute;n:</td>
                            <td id="tdListSubseccionTemp">
                                <select id="lstSubseccionTemp" name="lstSubseccionTemp">
                                    <option value="-1">Todos...</option>
                                </select>
                            </td>                            
                        </tr>
                        <tr>
                        	<td></td>
                            <td></td>    
                        	<td width="100" align="right" class="tituloCampo">Criterio:</td>
                            <td>
                                <input type="text" id="txtDescripcionBusqTemp" name="txtDescripcionBusqTemp" />
                            </td>
                            <td>
                                <input type="button" id="btnBuscarTempario" name="btnBuscarTempario" onClick="xajax_buscarTempario(xajax.getFormValues('frmBusMo'),xajax.getFormValues('formulario2'));" value="Buscar..."/>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
            <tr>
                <td>
                    <div id="tdListadoTemparioPorUnidad">
                        <table width="100%">
                            <tr class="tituloColumna">
                                <td width='8%'></td>
                                <td width='10%'>C&oacute;digo</td>
                                <td width='42%'>Descripci&oacute;n</td>
                                <td width='20%'>Secci&oacute;n</td>
                                <td width='20%'>Subsecci&oacute;n</td>
                            </tr>
                        </table>
                    </div>
                </td>
            </tr>
            <tr>
                <td align="right">
                    <hr>
                    <input type="button" value="Cancelar" onClick="byId('bus_mo').style.display = 'none';">
                </td>
            </tr>
        </table>
    </form>
</div>
<img class="close_window" src="<?php echo getUrl('img/iconos/close_dialog.png'); ?>" alt="cerrar" title="Cerrar" style="cursor:pointer;" onClick="byId('bus_mo').style.display = 'none';" border="0" />
</div>


<div class="window" id="tipo_precio_window" style="min-width:510px;display:none;">
	<div class="title" id="title_window_precio">
		Paquetes de servicio
	</div>
	<div class="content">
		<form id="formularioPrecio" name="formularioPrecio" onSubmit="return false;" style="margin:0px;padding:0px;" action="#">
			<input type="hidden" id="id_paquete" name="id_paquete" />
			<table>
				<tr>
					<td valign="top">
						<table class="insert_table" style="width:auto;">
							<tbody>						
								<tr>
									<td class="label">Precio Repuesto</td>
									<td align="left" id="tdPrecioRpto"></td>
									<td>
                                    <input type="hidden" id="hddIdPrecio" name="hddIdPrecio" value='1'/>
                                    <input type="hidden" id="hddIdPaquete" name="hddIdPaquete" />
                                    </td>
                                   
                                    
                                    <td>
                                <button type="buttom" id="guardar" onClick="xajax_actualizar_repuestos(xajax.getFormValues('formularioPrecio')); byId('tipo_precio_window').style.display = 'none';" ><img border="0" src="<?php echo getUrl('img/iconos/save.png'); ?>" alt="save"/>Actualizar</button>
                                </td>
                            </tr>
							</tbody>
						</table>
					</td>
				</tr>
			</table>
		</form>
	</div>
	<img class="close_window" src="<?php echo getUrl('img/iconos/close_dialog.png'); ?>" alt="cerrar" title="Cerrar" style="cursor:pointer;" onClick="byId('tipo_precio_window').style.display = 'none';" border="0" />
</div>

<script type="text/javascript" language="javascript">
	xajax_cargaLstEmpresaFinal('<?php echo $_SESSION["idEmpresaUsuarioSysGts"] ?>','',"id_empresa","field_id_empresa",0,"unico");
	//$("#id_empresa").attr('disabled','disabled');
	//$input.disabled = "disabled";
	
	xajax_listadoPaquetes(0,'id_paquete','ASC');
	
	function limpiar_select(){
	/*	var select_option = $("#id_empresa").find("option:not([selected])").hide();
		$("#id_empresa").find("optgroup").removeAttr('label');				*/
	}
	
	var theHandle = document.getElementById("title_window_2");
	var theRoot   = document.getElementById("edit_window_2");
	Drag.init(theHandle, theRoot);
	
	var theHandle = document.getElementById("title_window_unidad");
	var theRoot   = document.getElementById("bus_unidad");
	Drag.init(theHandle, theRoot);
	
	var theHandle = document.getElementById("title_window_art");
	var theRoot   = document.getElementById("bus_art");
	Drag.init(theHandle, theRoot);	
	
	var theHandle = document.getElementById("title_window_mo");
	var theRoot   = document.getElementById("bus_mo");
	Drag.init(theHandle, theRoot);	
	
	var theHandle = document.getElementById("title_window_precio");
	var theRoot   = document.getElementById("tipo_precio_window");
	Drag.init(theHandle, theRoot);	
</script>

