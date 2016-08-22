<?php
	require_once('../connections/conex.php');
	require_once('../inc_sesion.php');
	//require_once('../forms.inc.php');
	//validaModulo('an_listado_semanal');
	//conectar();
	@session_start();
	
	/* Validación del Módulo */
	require_once("../inc_sesion.php");
	if (!(validaAcceso("ga_solicitud_compra_list"))
	&& !(validaAcceso("ga_solicitud_compra_list","insertar"))){
		echo "
		<script>
			alert('Acceso Denegado');
			window.location = 'ga_solicitud_compra_list.php';
		</script>";
		
		exit;
	}
	/* Fin Validación del Módulo */
	
	$nombre_distribuidor = htmlentities(getmysql("SELECT nombre_empresa FROM pg_empresa
	WHERE id_empresa=".getempresa().";"));
	
	$logo_familia = getmysql("SELECT logo_familia FROM pg_empresa
	WHERE id_empresa=".getempresa().";");
	
	if ($_GET['omitirprimerasemana'] != "") {
		$ops = "&omitirprimerasemana=1";
	}

	
	//definiendo los estilos de los campos:
	$atributo_cantidad=
		array(
		'onkeyup'=>'recalcula(this);',
		'style'=>'width:90%;border:0px;text-align:center;',
		'maxlength'=>'4',
		'onkeypress'=>'return inputonlyint(event);');
		
	$atributo_unidad=
		array(
		'style'=>'width:94%;border:0px;text-align:center;',
		'readonly'=>'readonly');
	$atributo_codigo=
		array(
		'style'=>'width:94%;border:0px;text-align:center;',
		'onkeyup'=>'ku_buscarcodigo(event,this);');
		
	$atributo_descripcion=
		array(
		'style'=>'width:99%;border:0px;text-align:center;',
		'readonly'=>'readonly;',
		'class'=>'noprint');
		
	$atributo_precio=
		array(
		'onkeyup'=>'recalcula(null);',
		'style'=>'width:94%;border:0px;text-align:center;',
		'onkeypress'=>'return inputnum(event, true);',
		'onchange'=>'setformato(this);');
	
	$atributo_fecha_requerida=
		array(
		'style'=>'width:98%;border:0px;text-align:center;',
		'readonly'=>'readonly',
		'title'=>'Haga click para establecer la fecha');
		
	$atributo_observacion=
		array(
		'style'=>'width:99%;height:100%');
		
	$atributo_codigo_empleado=
		array(
		'style'=>'width:120px;');
	$tipo_compra[1]['onclick']="agregar(null,'cantidad0');desabilitar_stock();document.getElementById('cantidad1').focus();";
	$tipo_compra[2]['onclick']="agregar();document.getElementById('cantidad1').focus();";
	$tipo_compra[3]['onclick']="agregar();document.getElementById('cantidad1').focus();";
	$tipo_compra[4]['onclick']="agregar();document.getElementById('cantidad1').focus();";
	
	if ($modovista) {
		//redefiniendo:
		$desabilitar=
			array(
			'onclick'=>'',
			'readonly'=>'readonly',
			'onkeydown'=>'',
			'onkeyup'=>'',
			'onkeypress'=>'');
		$atributo_cantidad=array_merge($atributo_cantidad,$desabilitar);

		$atributo_unidad=array_merge($atributo_unidad,$desabilitar);
		$atributo_codigo=array_merge($atributo_codigo,$desabilitar);
		$atributo_descripcion=array_merge($atributo_descripcion,$desabilitar);

		$atributo_precio=array_merge($atributo_precio,$desabilitar);

		$atributo_fecha_requerida=array_merge($atributo_fecha_requerida,$desabilitar);

		$atributo_observacion=array_merge($atributo_observacion,$desabilitar);
		$tipo_compra[1]['onclick']="";
		$tipo_compra[2]['onclick']="";
		$tipo_compra[3]['onclick']="";
		$tipo_compra[4]['onclick']="";
	}
	
	$atributo_preciototal=
		array(
		'style'=>"border:0px;width:95%;text-align:center;font-weight:bold;");
		
	function printfecha($fecha){
		$r = htmlentities($fecha);
		if($fecha!="00-00-0000"){
			return $fecha;
		}
		return '';
	}
	
include('sa_proceso_compras_code.php');
	
//definiendo los campos del detalle
/*$campo = array(
'cantidad' 	=> inputTag("text","cantidad[]",null,$atributo_cantidad),
'unidad' => inputTag("hidden","unidad[]",null,$atributo_unidad),
'codigo' => inputTag("text","codigo[]",null,$atributo_codigo),
'descripcion' => inputTag("hidden","descripcion[]",null,$atributo_descripcion),
'precio' => inputTag("hidden","precio[]",null,$atributo_precio),
'fecha_requerida' => inputTag("hidden","fecha_requerida[]",null,$atributo_fecha_requerida),
);*/
//importar la vista:
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>.: SIPRE 2.0 :. Compras - Solicitudes de Compra</title>
    <link href="ga_solicitud_compras.css" rel="stylesheet" type="text/css" />
	
	<style type="text/css">
    <?php if($modovista): ?>
        .noprint{
            display:none;
        }
        .onlyprint{
            display:inline;
        }
    <?php endif; ?>
		tr img{
			cursor:pointer;
		}
    </style>
	
    <link rel="stylesheet" type="text/css" href="../style/styleRafk.css">
    
    <link rel="stylesheet" type="text/css" href="../js/domDragCompras.css">
	<script type="text/javascript" src="../vehiculos/vehiculos.inc.js"></script>
    <script type="text/javascript" src="../vehiculos/anajax.js"></script>
    <script type="text/javascript" src="../tables.inc.js"></script>

	<script type="text/javascript" language="javascript">
	function cambiar(){
		var f2=document.getElementById('freload');
		f2.submit();
	}

	var unlock=false;
	function validar(){
		var r = pvalidar('id_empresa','',"Empresa") &&
		pvalidar('id_departamento','',"Departamento") && 
		pvalidar('id_unidad_centro_costo','',"Unidad (Centro de Costo)") ;
		//recorriendo:
		var f=document.getElementById('solicitud');
		if(getRadioValue(f.elements['tipo_compra'])==null){
			alert('No ha especificado el Tipo de Compra');
			return false;
		}
		var c=f.elements['cantidad[]'];
		//var total= document.getelementById('epreciototal');
		if (c!=null){
			var o= getForceArray(c);
			for(var i=1;i<o.length;i++){
				if(parseInt(document.getElementById('cantidad'+i).value)!=0){
					r= r  && pvalidar('cantidad'+i,'numero','Cantidad') &&
						pvalidar('precio'+i,'float','Precio') &&
						pvalidar('fecha_requerida'+i,'','Fecha Requerida');
				}
				if (!r) return false;
			}		
		}
		//r = r && pvalidar('id_proveedor','','Proveedor')
		return r;
	}
	
	function enviar(){
		var f=document.getElementById('solicitud');
		unlock=true;
		if (validar()){
			f.submit();	
		}
	}
	
	function cargarSolicitud(){
		//llevar a ciclo desde php
		<?php
		if($cargar_datos != "") {
			echo $cargar_datos;
		} ?>
		//agregar();
		<?php if($_GET['view']=='print'){ ?>
			print();
		<?php } ?>
	}
	
	var rows=[];
	function calendario(obj){
		<?php if(!$modovista){ ?>
		Calendar.setup(obj);
		<?php } ?>
	}
	
	function detalles(){
		this.id_detalle_solicitud_compra='';	
		this.id_articulo='';
		this.cantidad='';
		this.unidad='';
		this.codigo='';
		this.descripcion='';
		this.fecha_requerida='';
	}
	
	function agregar(datos){
		var datos = datos || null;
		var deshabilitado = '';
		if (datos == null) {
			datos = new detalles();
			deshabilitado='disabled="disabled"';
		}
		//verifica si existe la necesidad de agregar
		
		var f = document.getElementById('solicitud');
		var c = f.elements['id_articulo[]'];
		if (c!=null){
			var o= getForceArray(c);
			for(var i=0;i<o.length;i++){
				if(o[i].value==''){
					return;
				}
			}
		}
		setRadioEnabled(f.elements['tipo_compra'],false);
		
	
		var newtr = new tableRow('cuerpo');
		var cantidad = newtr.addCell();
		var unidad = newtr.addCell();
		var codigo = newtr.addCell();
		var descripcion = newtr.addCell();
		var precio = newtr.addCell();
		var fecha_requerida = newtr.addCell();
		
		var t = getTotal()+1;
		cantidad.$.innerHTML = '<input type="text" id="cantidad'+t+'" name="cantidad[]" value="'+datos.cantidad+'" <?php echo getAttributes($atributo_cantidad); ?> />';
		cantidad.setClass('cantidad');
		cantidad.setId('ecantidad'+t);
		
		//unidad.$.innerHTML='<?php echo $campo['unidad']; ?>';
		unidad.$.innerHTML = '<span <?php echo getAttributes($atributo_unidad); ?> >'+datos.unidad+'</span>';
		unidad.setClass('unidad');
		unidad.setId('eunidad'+t);
		
		codigo.$.innerHTML = '<input value="'+datos.id_detalle_solicitud_compra+'" type="hidden" name="id_detalle_solicitud_compra[]"/>'+
		'<input type="hidden" id="id_articulo'+t+'" name="id_articulo[]" value="'+datos.id_articulo+'"/>'+
		'<input type="hidden" id="codigo'+t+'oculto" value="'+datos.codigo+'"/>'+
		'<input type="text" id="codigo'+t+'" name="codigo[]" autocomplete="off" value="'+datos.codigo+'" <?php echo getAttributes($atributo_codigo); ?> />';
		codigo.setClass('codigo');
		codigo.setId('ecodigo'+t);
		
		descripcion.$.innerHTML = '<input type="text" id="descripcion'+t+'" name="descripcion[]" value="'+datos.descripcion+'" <?php echo getAttributes($atributo_descripcion); ?>/><div class="onlyprint">'+datos.descripcion+'</div>';
		//descripcion.$.innerHTML=datos.descripcion;
		descripcion.setClass('descripcion');
		descripcion.setId('edescripcion'+t);
		
		//precio.$.innerHTML='<?php echo $campo['precio']; ?>';
		datos.precio = (typeof(datos.precio) != 'undefined') ? datos.precio : 0;
		precio.$.innerHTML = '<input type="text" id="precio'+t+'" name="precio[]" value="'+formato(parsenum(datos.precio))+'" <?php echo getAttributes($atributo_precio); ?>/>'
		precio.setClass('precio');
		precio.setId('eprecio'+t);
		
		fecha_requerida.$.innerHTML='<input type="text" value="'+datos.fecha_requerida+'" id="fecha_requerida'+t+'" name="fecha_requerida[]" <?php echo getAttributes($atributo_fecha_requerida); ?> />';		
		calendario({
			inputField : 'fecha_requerida'+t, // id del campo de texto
			ifFormat : "%d-%m-%Y", // formato de la fecha que se escriba en el campo de texto
			eventName : 'focus',
			button : 'fecha_requerida'+t // el id del botn que lanzar el calendario
		});
		fecha_requerida.setClass('fecha_requerida');
		fecha_requerida.setId('efecha_requerida'+t);
		
		//if (t != 0) {
			var e = newtr.addCell();
			e.$.innerHTML='<button id="elimina'+t+'" '+deshabilitado+' onclick="elimina('+(t-1)+',event);"><?php setImageTag("../img/iconos/ico_delete.png",null,array('title'=>'eliminar','class' => 'buttonimage')) ?></button>';
			e.setClass('noprint');
		//}
		newtr.num = t;
		newtr.setAttribute('id','ntr'+t);
		rows[rows.length]=newtr;
		var tp=t;
		if(t > 1) {
			tp = t - 1;
		}
		document.getElementById('precio'+(tp)).focus();
	}
	
	function habilita(obj){
		document.getElementById(obj).disabled=false;
	}
	
	function elimina(val,event){
		//if(event.currentTarget!=event.explicitOriginalTarget) return;
		//alert(event.currentTarget.id+' '+event.explicitOriginalTarget.id);
		//alert(event.currentTarget.id.substring(0,7)+' '+event.explicitOriginalTarget.id.substring(0,7));
		if(event.detail==0)return;
		if(utf8confirm("&iquest;Desea eliminar el Art&iacute;culo?")==false) return;
		var obj=rows[val];
		//var p=obj.parentNode;
		//p.removeChild(obj);
		obj.$.style.display="none";
		document.getElementById("cantidad"+obj.num).value=0;
		//document.getElementById("codigo"+obj.num).value=0;
		//document.getElementById("id_articulo"+obj.num).value=0;
		//document.getElementById("eprecio"+obj.num).innerHTML='';
		recalcula(null);
		agregar();
	}
	
	function getTotal(){
		var f=document.getElementById('solicitud');
		var c=f.elements['cantidad[]'];
		if (c==null){
			return 0;
		}
		var o= getForceArray(c);
		return o.length;
	}
	
	//---------------------------------------------------------------------------
	
	//busqueda AJAX repuesto
	function buscarcodigo(campo){
		var _obj=objeto(campo);
		//_obj.disabled=false;
		_obj.readOnly=false;
		if (_obj.value=="") {
			var lista= document.getElementById("ajaxlist");
			lista.style.visibility="hidden";
			_obj.focus();
			return;
		}
		var a= new Ajax();
		//a.loading=carga;
		//a.error=er;
		a.load= function(texto){
			var lista= document.getElementById("ajaxlist");
			lista.style.visibility="visible";
			var obj= _obj;//document.getElementById("cedula");
			lista.style.left=getOffsetLeft(obj)+"px";
			lista.style.top=getOffsetTop(obj)+"px";
			lista.style.margin=obj.offsetHeight+"px 0px 0px 0px";
			lista.innerHTML=texto;
		};
		var f=document.getElementById('solicitud');
		var tipo = getRadioValue(f.elements['tipo_compra']);
		a.sendget("ga_solicitud_compras_ajax.php","ajax_codigo="+_obj.value+"&cancelar="+_obj.id+"&id_proveedor"+document.getElementById('id_proveedor').value+"&tipo_compra="+tipo,false);
	}
	
	function cancelarcodigo(objeto){
		var lista= document.getElementById("ajaxlist");
		lista.style.visibility="hidden";
		//idlista=0;
		
		var obj1= document.getElementById(objeto+'oculto');
		var obj2= document.getElementById(objeto);
		
		//if (obj1.value!=""){
			obj2.value=obj1.value;
		//}
		//var obj3= document.getElementById("nombre");
		//obj3.focus();
	}
	
	var lp=-1;
	function ku_buscarcodigo(e,obj){
		var slista= document.getElementById("overclientes");
		var tecla = (document.all) ? e.keyCode : e.which;
		if(tecla==9) return; //tab
		if (tecla==40 || tecla==38){
			if (slista==null){ 
				return;
			}
			if (tecla==40){
				lp++;
			}else{
				lp--;
			}
			if(lp >= slista.childNodes.length){
				lp=0;
			}else if(lp<=-1){
				lp=slista.childNodes.length-1;
			}
			for (i=0;i<slista.childNodes.length;i++){
				if (lp!=i){
					var item = slista.childNodes.item(i);
					if (item.lastcolor!=null)
						item.style.background=item.lastcolor;
				}
			}
			var item = slista.childNodes.item(lp);
			if (item!=null)
				item.style.background="#FFCC66";
		}else if ((tecla==13) || ((objeto(obj).value.toString().length>0) && (objeto(obj).readOnly==false))){
			if ((lp!=-1) && (tecla==13)){
				var item = slista.childNodes.item(lp);
				cargarxml("articulo",item.accion,objeto(obj).id);
			}else{
				if(tecla==13){
					buscarcodigoexpress(obj);
				}
				buscarcodigo(obj);
			}
			lp=-1;
		}
	}
	
	function buscarcodigoexpress(obj){
		var f=document.getElementById('solicitud');
		//alert(obj);
		loadxml("ga_solicitud_compras_ajax.php",
		"codigoexpress",
		obj.value+"&objeto="+obj.id+"&tipo_seccion="+getRadioValue(f.elements['tipo_compra']));
	}
	
	function campoclientefocus(){
		cancelarcodigo();
	}
	
	function getRadioValue(radioobj){
		var obj= getForceArray(radioobj);
		for(var i=0;i<obj.length;i++){
			if(obj[i].checked){
				return obj[i].value;
			}
		}
	}	
	
	function setRadioEnabled(radioobj,val){
		var obj= getForceArray(radioobj);
		for(var i=0;i<obj.length;i++){
			if(!obj[i].checked){
				obj[i].disabled=!val;
			}
		}
	}
	
	function desabilitar_stock(){
		var f=document.getElementById('solicitud');
		if(getRadioValue(f.elements['tipo_compra'])==1){
			//desabilitar 
			document.getElementById('sustitucion1').disabled=true;
			document.getElementById('sustitucion2').disabled=true;
			document.getElementById('presupuestado0').disabled=true;
			document.getElementById('justificacion_compra').disabled=true;
		}
	}
	
	function cargarxml(cmd,id,objeto){
		//alert(id);
		if(cmd=="articulo"){
			var f=document.getElementById('solicitud');
			var c=f.elements['id_articulo[]'];
			if (c!=null){
				var o= getForceArray(c);
				for(var i=0;i<o.length;i++){
					if(o[i].value==id){
						var p= o[i].parentNode.parentNode;
						if(p.style.display=="none"){
							if(utf8confirm("El articulo se elimin&oacute; anteriormente, &iquest;desea agregarlo de nuevo? \nNota: se establecer&aacute; cantidad en 1")){
								p.style.display="";
								cancelarcodigo(objeto);
								var ob = document.getElementById("cantidad"+p.id.substring(3));
								ob.value=1;
								recalcula(null);
								ob.focus();
								return;
							}
						}else{
							utf8alert("Ya est&aacute; incluido");
						}
						
						return;
					}
				}
			}
		}
		loadxml("ga_solicitud_compras_ajax.php",cmd,id+"&objeto="+objeto+"&id_proveedor="+document.getElementById('id_proveedor').value);
	}
	
	function cargarprecios(){
		//return;//deprecated
		//obtener variables:
		var postvars='',cantidades='';
		var f=document.getElementById('solicitud');
		var c=f.elements['id_articulo[]'];
		if (c!=null){
			var o= getForceArray(c);
			var cant= getForceArray(f.elements['cantidad[]']);
			for(var i=0;i<o.length-1;i++){
					postvars=postvars+"id_articulo["+i+"]="+o[i].value+"&";
					cantidades=cantidades+"cantidad["+i+"]="+cant[i].value+"&";
			}
		}
		

		if(postvars=='') return;
		var proveedor=document.getElementById('id_proveedor');
		/*if(proveedor.value=='') {
			
			return;
		}*/
		postvars=postvars+cantidades+"id_proveedor="+proveedor.value;
		loadpostxml("ga_solicitud_compras_ajax.php",postvars);
	}
	
	function comparaprecio(campo,valor){
		var obj=document.getElementById(campo);
		var antiguo = parsenum(obj.value);
		var val = parsenum(valor);
		if(val==0 || antiguo==0){
			obj.style.color="#000000";
		}
		else if(antiguo==val){
			obj.style.color="#3F8F3E";
		}		
		else if(antiguo>val){
			obj.style.color="#0000FF";
		}else{
			obj.style.color="#FF0000";
		}
		if(val!=0){
			obj.value=formato(val);
		}else {
			obj.value="";
		}
		
	}
	
	function recalcula(obj){
		if(obj!=null){
			if (parsenum(obj.value)==0){
				alert('No puede especificar cantidad 0');
				obj.focus();
				return false;
			}
		}
		var f=document.getElementById('solicitud');
		var c=f.elements['cantidad[]'];
		var total=0;
		//var total= document.getelementById('epreciototal');
		if (c!=null){
			var o= getForceArray(c);
			for(var i=0;i<o.length;i++){
				if(document.getElementById('id_articulo'+(i+1)).value!=''){
					var precio=document.getElementById('precio'+(i+1)).value;
					total=total+(parsenum(o[i].value)*parsenum(precio));
				}
			}
			comparaprecio('epreciototal',total);
		}
	}
	
	//-------------------------------------------------------------------------------------------------
	
	function cargar_empresa(obj){
		if(obj != null){
			valor=obj.value;
		}
		loadxml("ga_solicitud_compras_ajax.php",'empresa',valor);
	}
	
	function cargar_departamento(obj){
		if(obj!=null){
			valor=obj.value;
		}
		loadxml("ga_solicitud_compras_ajax.php",'departamento',valor);
	}
	
	function cargar_unidad_centro_costo(obj){
		if(obj!=null){
			valor=obj.value;
		}
		loadxml("ga_solicitud_compras_ajax.php",'unidad_centro_costo',valor);
	}
	
	//--------------------------------solicitudes
	
	function enviarsolicitud(){
		var f=document.getElementById('forma_estado_solicitud');
		var r= pvalidar('codigo_empleado','','C&oacute;digo Empleado');
		if(r){
			f.submit();
		}
	}
	
	function open_win(){
		var theHandle = document.getElementById("divFlotanteTitulo");
		var theRoot   = document.getElementById("divFlotante");
		
		theRoot.style.display = "";
		setCenter("divFlotante",true);
		
		document.getElementById("codigo_empleado").focus();
		document.getElementById("codigo_empleado").select();
	}
	
	function close_win(){
		window.location="ga_solicitud_compras_editar.php?view=1&id=<?php echo $id_solicitud_compra; ?>";
	}
    </script>
	<?php
	//$xajax->printJavascript('controladores/xajax/'); 
	getXajaxJavascript();
	includeScripts(); ?>
</head>

<body onload="cargarSolicitud();"<?php //if($_GET['view']=="print"){echo 'onload="print();"'; }else{ echo 'onload="agregar();"';}?>>
<div id="ajaxlist" class="ajaxlist"></div>

<div id="divGeneralVehiculos">
	<div><?php if ($_GET['view'] != "print") { include("banner_compras.php"); } ?></div>
    
    <div id="divInfo" class="print">
    	<br>
	<?php setStartForm("solicitud","ga_solicitud_compras_guardar.php","class=formulario,onsubmit=return false;"); ?>
    	<input type="hidden" id="id_solicitud_compra" name="id_solicitud_compra" value="<?php echo $id_solicitud_compra; ?>"/>
    	<input type="hidden" id="id_empleado_solicitud" name="id_empleado_solicitud" value="<?php echo $id_empleado_solicitud; ?>"/>
        
        <table border="1" style="border-collapse:collapse; border-color:#000000;" width="100%">
        <tr valign="top">
        	<td colspan="3" rowspan="2" valign="middle">
            	<table border="0" width="100%">
                <tr>
                	<td id="tdlogo" width="25%"><?php setImageTag("../img/logos/logo_grupo_automotriz.jpg","logo","style=width:150px;") ?></td>
                	<td id="tdtitulo" class="title" width="60%">SOLICITUD DE COMPRAS Y SERVICIOS</td>
                	<td width="15%">
					<?php
					if ($id_solicitud_compra != '') {
						echo '<img src="../clases/barcode128.php?codigo='.$id_solicitud_compra.'&bw=2&pc=1" alt="code" />';
					} ?>
                    </td>
                </tr>
                </table>
            </td>
            <td>
            	<table cellspacing="0" width="100%">
                <tr align="left"><td>N&deg;</td></tr>
                <tr><td align="right"><span id="numero_solicitud"><?php echo $codigo_empresa;?></span>-<span><?php echo $numero_solicitud;?></span></td></tr>
                </table>
            </td>
        </tr>
        <tr valign="top">
            <td>
            	<table cellspacing="0" width="100%">
                <tr align="left"><td class="tdetiqueta">Fecha</td></tr>
                <tr><td align="right"><?php echo ($fecha_solicitud) ? $fecha_solicitud : date('d-m-Y'); ?></td></tr>
                </table>
            </td>
        </tr>
        <tr valign="top">
        	<td colspan="3">
            	<table cellspacing="0" width="100%">
                <tr align="left"><td class="tdetiqueta">Compañia:</td></tr>
                <tr align="left">
                	<td>
                    <span class="noprint">
						<?php
                        //$empresas = getMysqlAssoc("SELECT id_empresa, nombre_empresa_sucursal FROM sa_v_empresa_sucursal WHERE sucursales = 0 AND id_empresa <> 100 ORDER BY nombre_empresa_sucursal ASC",$conex);
						$empresas = getMysqlAssoc("SELECT
							id_empresa_reg AS id_empresa,
							CONCAT_WS(' ', nombre_empresa, nombre_empresa_suc) AS nombre_empresa_sucursal
						FROM vw_iv_empresas_sucursales
						WHERE id_empresa <> 100
						ORDER BY nombre_empresa_sucursal ASC",$conex);
                        setInputSelect("id_empresa",$empresas,$id_empresa,array('onchange'=>'cargar_empresa(this);','class'=>'noprint','style'=>'width: 100%')); ?>
                    </span>
                    <span class="onlyprint">
						<?php echo htmlentities($nombre_empresa); //if($id_empresa!=0) {echo getmysql("select nombre_empresa from pg_empresa where id_empresa=".$id_empresa.";");} ?>
                    </span>
                    </td>
                </tr>
                </table>
            </td>
            <td>
            	<table cellspacing="0" width="100%">
                <tr align="left"><td class="tdetiqueta">Código</td></tr>
                <tr>
                	<td align="right" id="codigo_empresa"><?php echo $codigo_empresa; ?></td>
                </tr>
                </table>
            </td>
		</tr>
        <tr valign="top">
        	<td width="28%">
            	<table cellspacing="0" width="100%">
                <tr align="left"><td class="tdetiqueta">Dependencia:</td></tr>
                <tr>
                	<td align="right">
                        <span class="noprint" id="capa_departamento">
                            <?php
                            $departamentos = getMysqlAssoc(sprintf("SELECT * FROM pg_departamento dep WHERE dep.id_empresa = %s;", $id_empresa), $conex);
                            setInputSelect("id_departamento", $departamentos, $id_departamento, array('onchange'=>'cargar_departamento(this);','class'=>'noprint','style'=>'width: 100%')); ?>
                        </span>
                        <span class="onlyprint">
                        <?php echo htmlentities($nombre_departamento); //if($id_empresa!=0) {echo getmysql("select nombre_empresa from pg_empresa where id_empresa=".$id_empresa.";");} ?>
                        </span>
                    </td>
                </tr>
                </table>
            </td>
        	<td width="28%">
            	<table cellspacing="0" width="100%">
                <tr align="left"><td class="tdetiqueta">Código</td></tr>
                <tr>
                	<td align="right" id="codigo_departamento"><?php echo $codigo_departamento; ?></td>
                </tr>
                </table>
            </td>
        	<td width="28%">
            	<table cellspacing="0" width="100%">
                <tr align="left"><td class="tdetiqueta">Unidad (Centro de Costo):</td></tr>
                <tr>
                	<td align="right">
                        <span class="noprint" id="capa_unidad_centro_costo">
                            <?php
                            $unidades = getMysqlAssoc("select id_unidad_centro_costo,nombre_unidad_centro_costo from pg_unidad_centro_costo where id_departamento=".$id_departamento.";",$conex);
                            setInputSelect("id_unidad_centro_costo",$unidades,$id_unidad_centro_costo,array('onchange'=>'cargar_unidad_centyro_costo(this);','class'=>'noprint','style'=>'width: 100%'));
                            ?>
                        </span>
                        <span class="onlyprint">
                        <?php echo htmlentities($nombre_unidad_centro_costo); //if($id_empresa!=0) {echo getmysql("select nombre_empresa from pg_empresa where id_empresa=".$id_empresa.";");} ?>
                        </span>
                    </td>
                </tr>
                </table>
            </td>
        	<td width="16%">
            	<table cellspacing="0" width="100%">
                <tr align="left"><td class="tdetiqueta">Código:</td></tr>
                <tr>
                	<td align="right" id="codigo_unidad_centro_costo"><?php echo $codigo_unidad_centro_costo; ?></td>
                </tr>
                </table>
            </td>
        </tr>
        <tr valign="top">
        	<td colspan="4">
            	<table cellspacing="0" width="100%">
                <tr align="left"><td class="tdetiqueta">Tipo de Compra:</td></tr>
                <tr>
                	<td align="right">
                    	<table cellpadding="0" cellspacing="0" width="100%">
						<?php
                        $queryTipoCompra = sprintf("SELECT * FROM ga_tipo_seccion WHERE id_tipo_seccion <> 1 ORDER BY tipo_seccion");
                        $rsTipoCompra = mysql_query($queryTipoCompra);
                        if (!$rsTipoCompra) die(mysql_error()."<br><br>Line: ".__LINE__);
                        $contFila = 0;
                        while ($rowTipoCompra = mysql_fetch_assoc($rsTipoCompra)) {
                            $contFila ++;
                
                            if (fmod($contFila, 4) == 1)
                                echo "<tr>";
                            
                            echo "<td width=\"25%\" valign=\"top\">";
                                setRadioInputTag("tipo_compra",$rowTipoCompra['id_tipo_seccion'],$rowTipoCompra['tipo_seccion'],$tipo_compra[$rowTipoCompra['id_tipo_seccion']]);
                            echo "</td>";
                            
                            if (fmod($contFila, 4) == 0)
                                echo "</tr>";
                        }
                        ?>
                        </table>
					</td>
                </tr>
                </table>
            </td>
        </tr>
        <tr>
        	<td colspan="4" id="encabezado"><div class="titular">Descripci&oacute;n del Material o Servicio</div></td>
        </tr>
        </table>
		
		<div id="detalles">
			<table id="tabladetalle">
            <tr class="tituloColumna">
                <td class="cantidad">Cantidad</td>
                <td class="unidad">Unidad</td>
                <td class="codigo">C&oacute;digo</td>
                <td class="descripcion">Descripci&oacute;n</td>
                <td class="precio">Precio</td>
                <td class="fecha_requerida">Fecha Requerida</td>
            </tr>
            <tbody id="cuerpo">
            </tbody>
            <tr>
                <td class="cantidad"></td>
                <td class="unidad"></td>
                <td class="codigo"></td>
                <td align="right" class="descripcion"><strong>Total:</strong></td>
                <td align="center" class="precio">
                    <input readonly="readonly" <?php setAttributes($atributo_preciototal); ?> id="epreciototal" />
                </td>
                <td class="fecha_requerida"></td>
            </tr>
			</table>
            
            <!---->
            
            <table border="1" style="border-collapse:collapse; border-color:#000000; border-top:none;" width="100%">
            <tr valign="top">
            	<td colspan="2">
                	<table cellspacing="0" width="100%">
                    <tr align="left"><td class="tdetiqueta"><strong>Proveedor Sugerido y su Justificaci&oacute;n</strong></td></tr>
                    <tr align="left">
                        <td>
                        <span class="noprint">
                            <?php
                            $proveedores = getMysqlAssoc("SELECT id_proveedor, nombre FROM cp_proveedor ORDER BY nombre;", $conex);
                            setInputSelect("id_proveedor",$proveedores,$id_proveedor,array('onchange'=>'cargarprecios();','class'=>'noprint'));
                            ?>
                        </span>
                        </td>
					</tr>
                    <tr align="left">
                        <td>
                        <span class="noprint">
                            <!--<span class="noprint"> Total: <input readonly="readonly" style="border:0px;" id="epreciototal" /></span>-->
                            <?php setInputArea('justificacion_proveedor',htmlentities($justificacion_proveedor),$atributo_observacion,120); ?>
                        </span>
                        <div class="onlyprint">
                            <?php echo '<strong>'.htmlentities($nombre_proveedor).'</strong><br />'.htmlentities($justificacion_proveedor);
                            //if($id_proveedor!=0) {echo getmysql("select nombre from cp_proveedor where id_proveedor=".$id_proveedor.";");} ?>
                        </div>
                        </td>
					</tr>
                    </table>
                </td>
            	<td colspan="2">
                	<table cellspacing="0" width="100%">
                    <tr align="left"><td class="tdetiqueta"><strong>Observaciones:</strong></td></tr>
                    <tr align="left">
                    	<td>
                        <span class="noprint">
							<?php setInputArea('observaciones',htmlentities($observaciones),$atributo_observacion,120); ?>
                        </span>
                        <span class="onlyprint">
							<?php echo htmlentities($observaciones); ?>
                        </span>
                        </td>
                    </tr>
                    </table>
                </td>
            </tr>
            <tr height="24">
            	<td class="tituloColumna" colspan="4">Para ser llenado para todo tipo de compra (excepto para las compras de material de stock del almac&eacute;n)</td>
            </tr>
            <tr valign="top">
            	<td>
                	<table cellspacing="0" width="100%">
                    <tr align="left"><td class="tdetiqueta"><strong>Sustituci&oacute;n o Adici&oacute;n</strong></td></tr>
                    <tr align="left">
                    	<td>
                        <span class="noprint">
							<?php
                            setRadioInputTag("sustitucion",1,"Sustituci&oacute;n",$sustitucion[1]);
                            echo '&nbsp;';
                            setRadioInputTag("sustitucion",2,"Adici&oacute;n",$sustitucion[2]);
                            ?>
                        </span>
                        <span class="onlyprint">
                            <?php
                            if ($v_sustitucion == 1) {
                                echo 'SUSTITUCI&Oacute;N';
                            } else if($v_sustitucion == 2) {
                                echo 'ADICI&Oacute;N';							
                            } ?>
                        </span>
                        </td>
                    </tr>
                    </table>
                </td>
                <td colspan="3" rowspan="2">
                	<table cellspacing="0" width="100%">
                    <tr align="left"><td class="tdetiqueta"><strong>Justificaci&oacute;n de la Compra:</strong></td></tr>
                    <tr align="left">
                    	<td>
                        <span class="noprint">
							<?php setInputArea('justificacion_compra',htmlentities($justificacion_compra),$atributo_observacion,220); ?>
                        </span>
                        <span class="onlyprint">
                            <?php echo htmlentities($justificacion_compra); ?>
                        </span>
                        </td>
                    </tr>
                    </table>
				</td>
            </tr>
            <tr valign="top">
            	<td>
                	<table cellspacing="0" width="100%">
                    <tr align="left"><td class="tdetiqueta"><strong>Presupuestado (S/N):</strong></td></tr>
                    <tr align="left">
                    	<td>
                        <span class="noprint">
                            <?php setMultipleInputTag("presupuestado",0,"Presupuestado",$presupuestado[0]); ?>
                        </span>
                        <span class="onlyprint">
                            <?php
                            if ($v_presupuestado == 1) {
                                echo 'PRESUPUESTADO';
                            } else if ($v_presupuestado == 2) {
                                echo '-';							
                            } ?>
                        </span>
                        </td>
                    </tr>
                    </table>
                </td>
            </tr>
            <tr valign="top">
            	<td colspan="2"><div class="titular">UNIDAD SOLICITANTE</div></td>
                <td colspan="2"><div class="titular">GCIA. DE COMPRAS</div></td>
			</tr>
            <tr valign="top">
            	<td width="25%">
                	<table cellspacing="0" width="100%">
                    <tr align="left"><td colspan="2">Solicitado Por:</td></tr>
                    <tr align="left"><td colspan="2">Nombre y Firma:</td></tr>
                    <tr><td align="center" colspan="2"><?php echo htmlentities($nombre_empleado_solicitud); ?><br />&nbsp;</td></tr>
                    <tr align="left">
                    	<td>N&deg; Empleado:</td>
                        <td><?php echo htmlentities($codigo_empleado_solicitud); ?></td>
					</tr>
                    <tr align="left">
                    	<td>Fecha:</td>
                        <td><?php echo printfecha($fecha_empleado_solicitud); ?></td>
					</tr>
                    </table>
                </td>
                <td width="25%">
                	<table cellspacing="0" width="100%">
                    <tr align="left"><td colspan="2">Aprobado Por:</td></tr>
                    <tr align="left"><td colspan="2">Nombre y Firma:</td></tr>
                    <tr><td align="center" colspan="2"><?php echo htmlentities($nombre_empleado_aprobacion); ?><br />&nbsp;</td></tr>
                    <tr align="left">
                    	<td>N&deg; Empleado:</td>
                        <td><?php echo htmlentities($codigo_empleado_aprobacion); ?></td>
					</tr>
                    <tr align="left">
                    	<td>Fecha:</td>
                        <td><?php echo printfecha($fecha_empleado_aprobacion); ?></td>
					</tr>
                    </table>
                </td>
                <td width="25%">
                	<table cellspacing="0" width="100%">
                    <tr align="left"><td colspan="2">Conformado Por:</td></tr>
                    <tr align="left"><td colspan="2">Nombre y Firma:</td></tr>
                    <tr><td align="center" colspan="2"><?php echo htmlentities($nombre_empleado_conformacion); ?><br />&nbsp;</td></tr>
                    <tr align="left">
                    	<td>N&deg; Empleado:</td>
                        <td><?php echo htmlentities($codigo_empleado_conformacion); ?></td>
					</tr>
                    <tr align="left">
                    	<td>Fecha:</td>
                        <td><?php echo printfecha($fecha_empleado_conformacion); ?></td>
					</tr>
                    </table>
                </td>
                <td width="25%">
                	<table cellspacing="0" width="100%">
                    <tr align="left"><td colspan="2">Procesado Por:</td></tr>
                    <tr align="left"><td colspan="2">Nombre y Firma:</td></tr>
                    <tr><td align="center" colspan="2"><?php echo htmlentities($nombre_empleado_proceso); ?><br />&nbsp;</td></tr>
                    <tr align="left">
                    	<td>N&deg; Empleado:</td>
                        <td><?php echo htmlentities($codigo_empleado_proceso); ?></td>
					</tr>
                    <tr align="left">
                    	<td>Fecha:</td>
                        <td><?php echo printfecha($fecha_empleado_proceso); ?></td>
					</tr>
                    </table>
                </td>
            </tr>
			<?php if ($estado >= 5) { //?>
            <tr>
            	<td class="titular" colspan="4">Motivo <?php echo $estado_solicitud ?>:</td>
            </tr>
            <tr>
            	<td colspan="4">
                	<table cellspacing="0" width="100%">
                    <tr align="left">
                        <td width="8%">Empleado:</td>
                        <td width="18%"><?php echo htmlentities($nombre_empleado_condicionamiento); ?></td>
                        <td width="8%">Fecha:</td>
                        <td width="66%"><?php echo printfecha($fecha_empleado_condicionamiento); ?></td>
                    </tr>
                    <tr align="left">
                    	<td class="tdetiqueta" colspan="4"><?php echo htmlentities($motivo_condicionamiento); ?></td>
                    </tr>
                    </table>
				</td>
			</tr>
			<?php } ?>
            </table>
            
            <table width="100%">
            <tr>
                <td width="28%">N&deg; de Actualizaci&oacute;n: 01</td>
                <td width="44%">Original: Gerencia de Compras / Duplicado: Unidad Solicitante</td>
                <td width="28%">COM-PR-1.2-F01/Fecha: ##/####</td>
            </tr>
            </table>
		</div>
        
            <table class="noprintabsolute" width="100%">
            <tr>
                <td align="right">
                    <hr>
				<?php
				if (!$modovista) {	
                    setButton("button",imageTag("../img/iconos/ico_save.png",null,array('class' => 'buttonimage'))." Guardar",array('onclick'=>'enviar();'));
                    
                    if ($id_solicitud_compra) {
                        setButton("button",imageTag("../img/iconos/ico_delete.png",null,array('class' => 'buttonimage'))." Cancelar",array('onclick'=>'window.location=\'ga_solicitud_compras_editar.php?view=1&id='.$id_solicitud_compra.'\''));
                    }
				} else if ($modovista) { // IMPLEMENTANDO EL MODO VISTA
					setButton("button",imageTag("../img/iconos/ico_aceptar.gif",null,array('class' => 'buttonimage'))." Procesar",array('onclick'=>'xajax_aprobar_solicitud('.$id_solicitud_compra.')'));
					setButton("button",imageTag("../img/iconos/ico_print.png",null,array('class' => 'buttonimage'))." Imprimir",array('onclick'=>'window.open(\'ga_solicitud_compras_editar.php?view=print&id='.$id_solicitud_compra.'\'),\'_blank\''));
					setButton("button",imageTag("../img/iconos/ico_edit.png",null,array('class' => 'buttonimage'))." Editar",array('onclick'=>'window.location=\'ga_solicitud_compras_editar.php?view=e&id='.$id_solicitud_compra.'\''));
					setButton("button",imageTag("../img/iconos/ico_return.png",null,array('class' => 'buttonimage'))." Regresar",array('onclick'=>'window.location=\'ga_solicitud_compra_list.php\''));
					
					setStartForm("forma_estado_solicitud","ga_solicitud_compras_estado.php","class=formulario");
					setInputTag('hidden','id_solicitud_compra',$id_solicitud_compra);
					setInputTag('hidden',"estado",$estado); ?>
					
					<div style="display:none;">
						<br>
						<table class="tablap">
						<caption><strong>Proceso de solicitud de Compra</strong></caption>
						<tr align="left">
							<td align="right"><strong>Estado de la Solicitud:</strong></td>
							<td><?php echo '<div> '.htmlentities($estado_solicitud).'</div>'; ?></td>
						</tr>
					<?php if ($estado < 4) { ?>
						<tr align="left">
							<td align="right"><strong>Ingrese su c&oacute;digo de empleado:</strong></td>
							<td><?php setInputTag("text","codigo_empleado","",$atributo_codigo_empleado); ?></td>
						</tr>
						<tr>
							<td colspan="2">
							<?php setButton("button",imageTag("iconos/envia.png",null,array('class' => 'buttonimage')).$nombre_estado,array('onclick'=>'enviarsolicitud();')); ?>
							</td>
						</tr>
						<tr>
							<td colspan="2">Nota: necesita permisos para esta operaci&oacute;n</td>
						</tr>
					<?php } ?>
						</table>
					</div>
                <?php
				} ?>
                </td>
            </tr>
            </table>
	<?php setEndForm(); ?>
    </div>
    
	<div>
	<?php
    if ($_GET['view'] != "print") {
		include("pie_pagina.php");
	} ?>
    </div>
</div>
</body>
</html>
<?php include("sa_form_proceso_solicitud.php"); ?>