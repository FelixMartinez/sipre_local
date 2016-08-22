<?php
@session_start();
header('Content-type: application/vnd.ms-excel');
header("Content-Disposition: attachment; filename=sa_informe_paquetes.xls");
header("Pragma: no-cache");
header("Expires: 0");

//error_reporting(E_ALL);
//ini_set("display_errors", 1);

//NO NECESARIOS PORQUE NO SE USA XAJAX
//require_once("../inc_sesion.php");
//
////implementando xajax;
	require_once("../control/main_control.inc.php");
	require_once("../control/iforms.inc.php");
	require_once("../control/funciones.inc.php");
//
//require_once ("../../connections/conex.php");//lo necesita ac_iv_general
//include("../controladores/ac_iv_general.php");//tiene la funcion listado de empresas final

function load_page($args=''){//$page,$maxrows,$order,$ordertype,$capa,
		
		setLocaleMode();
		
		$c = new connection();
		$c->open();
		
		
		$sa_paquetes = $c->sa_v_paquetes;
		$query = new query($c);
		$query->add($sa_paquetes);
		$paginador = new fastpaginator('xajax_load_page',$args,$query);
		$arrayFiltros=array(
			'Empresa'=>array(
				'change'=>'h_empresa',
				'addevent'=>"obj('empresa').value='';"
			),
			'busca'=>array(
				'title'=>'B&uacute;squeda',
				'event'=>'restablecer();'
			),
			'fecha'=>array(
				'title'=>'Fecha'
			)
		);
		$argumentos = $paginador->getArrayArgs();
		$aplica_iva=($argumentos['iva']==1);
		$id_empresa=$argumentos['h_empresa'];
				
		if($id_empresa==null){
			$idEmpresaConfig = $_SESSION['idEmpresaUsuarioSysGts'];
		}else{
			$query->where(new criteria(sqlEQUAL,$sa_paquetes->id_empresa,$id_empresa));
			$idEmpresaConfig = $id_empresa;
		}
		
		$queryEmpresa = $c->pg_empresa->doSelect($c, new criteria(sqlEQUAL,'id_empresa',$idEmpresaConfig));
		$esCombo = $queryEmpresa->paquete_combo;
		
		$rec=$paginador->run();
		if($rec){
			foreach($rec as $v){
				//buscando las unidades basicas del paquete
				$unidades=$c->sa_v_informe_tempario_unidades->doSelect($c, new criteria(sqlEQUAL,'id_paquete',$v->id_paquete))->getAssoc('id_paq_unidad','nombre_unidad_basica');
				$lista_unidades=implode(',',$unidades);
				$html.='<table class="order_table"><col width="20%" /><col width="40%" /><col width="40%" /><thead>';
				
				$html.='<tr><td style="background-color: #bfbfbf; font-weight:bold;">PAQUETE</td><td style="background-color: #bfbfbf; font-weight:bold;">Descripci&oacute;n</td><td style="background-color: #bfbfbf; font-weight:bold;">Unidades</td></tr></thead><tbody>';
				$html.='<tr><td>'.$v->codigo_paquete.'</td><td>'.$v->descripcion_paquete.'</td><td>'.$lista_unidades.'</td></tr></tbody></table>';
				
				
				$html.='<table class="order_table"><col width="20%" /><col width="40%" /><col width="20%" /><thead><tr><td style="background-color: #bfbfbf; font-weight:bold;">Codigo</td><td style="background-color: #bfbfbf; font-weight:bold;">Descripci&oacute;n</td><td style="background-color: #bfbfbf; font-weight:bold;">Unidad</td><td style="background-color: #bfbfbf; font-weight:bold;">Cantidad</td>';
				if($esCombo){ 
					$html.='<td style="background-color: #bfbfbf; font-weight:bold;">Precio</td><td style="background-color: #bfbfbf; font-weight:bold;">Importe</td><td style="background-color: #bfbfbf; font-weight:bold;">Costo</td>';
				}
				$html.='</tr></thead><tbody>';
				//cargando los detalles de tempario
				$sa_v_paq_tempario=$c->sa_paq_tempario; 
				$sa_tempario= new table('sa_v_tempario','',$c);
				$join= $sa_tempario->join($sa_v_paq_tempario,$sa_tempario->id_tempario,$sa_v_paq_tempario->id_tempario);
				$qdet=new query($c);
				$qdet->add($join);
				$qdet->where(new criteria(sqlEQUAL,$sa_v_paq_tempario->id_paquete,$v->id_paquete));

				$recdet=$qdet->doSelect();
				if($recdet){
					foreach($recdet as $temp){
						$html.='<tr><td>'.$temp->codigo_tempario.'</td><td>'.$temp->descripcion_tempario.'</td><td>'.$temp->descripcion_modo.'</td><td>N/A</td>';
						if($esCombo){ $html.='<td>'.$temp->precio.'</td><td>'.$temp->precio.'</td><td>'.$temp->costo.'</td>'; }
						$html.='</tr>';
					}
				}
				
				$sa_v_paq_repuestos=$c->sa_paquete_repuestos;
				$iv_articulos= new table('iv_articulos','',$c);
				$join= $iv_articulos->join($sa_v_paq_repuestos,$iv_articulos->id_articulo,$sa_v_paq_repuestos->id_articulo);
				$qdet=new query($c);
				$qdet->add($join);
				$qdet->where(new criteria(sqlEQUAL,$sa_v_paq_repuestos->id_paquete,$rec->id_paquete));
				//$r->alert($qdet->getSelect());
				$recdet=$qdet->doSelect();
				$recdet=$qdet->doSelect();
				if($recdet){
					foreach($recdet as $rep){
						$html.='<tr><td>'.$rep->codigo_articulo.'</td><td>'.$rep->descripcion.'</td><td>'.$rep->unidad.'</td><td>'.$rep->cantidad.'</td>';
						if($esCombo){ $html.='<td>'.$rep->precio.'</td><td>'.$rep->precio.'</td><td></td>'; }
						$html.='</tr>';
					}
				}
				
				$html.='</tbody></table><br />';
			}
		}
		$html.='<br />Informe Generado el '.date(DEFINEDphp_DATETIME12).' - Empresa: '.$argumentos['Empresa'].'<strong>'.$tiva.'</strong>';
		
		return $html;
	}
    

echo "<b>SERVICIOS</b><br>";
echo "<table><tr><td colspan='2'>Reporte Paquetes</td></tr></table><br>";
    
echo load_page($_GET['valBusq']);