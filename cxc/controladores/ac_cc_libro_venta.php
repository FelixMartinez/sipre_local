<?php 
function buscarEmpresa($frmBuscarEmpresa){
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s|%s",
		$frmBuscarEmpresa['hddObjDestino'],
		$frmBuscarEmpresa['hddNomVentana'],
		$frmBuscarEmpresa['txtCriterioBuscarEmpresa']);
	
	$objResponse->loadCommands(listadoEmpresasUsuario(0, "id_empresa_reg", "ASC", $valBusq));
		
	return $objResponse;
}

function cargarModulos(){
	$objResponse = new xajaxResponse();
	
	$queryModulos = sprintf("SELECT * FROM pg_modulos");
	$rsModulos = mysql_query($queryModulos);
	if (!$rsModulos) return $objResponse->alert(mysql_error()."\n\nLINE: ".__LINE__);

	$html = "<table border=\"0\" width=\"100%\">";
	$cont = 1;
	while ($rowModulos = mysql_fetch_array($rsModulos)) {
		if (fmod($cont, 4) == 1)
			$html .= "<tr align=\"center\" height=\"22\">";
				$html .= sprintf("<td><input type=\"checkbox\" id=\"cbxModulo\" name=\"cbxModulo[]\" checked=\"checked\" value=\"%s\"/>%s</td>",
					$rowModulos['id_enlace_concepto'],
					$rowModulos['descripcionModulo']);
		if (fmod($cont, 4) == 0)
			$html .= "</tr>";
	
		$cont++;	
	}
	$html .= "</table>";
	
	$objResponse->assign("tdModulos","innerHTML",$html);
	
	return $objResponse;
}

function validaEnvia($valForm){
	$objResponse = new xajaxResponse();
	
	if (isset($valForm['cbxModulo'])) {
		foreach ($valForm['cbxModulo'] as $pos => $valor){
			$idModulos .= sprintf("%s,",$valor);
		}
		$idModulos = substr($idModulos, 0, (strlen($idModulos)-1));
	}
	
	if($valForm['txtFechaOrigen'] == "" || $valForm['txtFechaFinal'] == ""){
		$objResponse->alert("Por Favor Coloque el Rango de Fechas");
	}elseif(strtotime($valForm['txtFechaOrigen']) <= strtotime($valForm['txtFechaFinal'])){
		if ($idModulos != ''){
			$objResponse->script(sprintf("
										window.open('cc_libro_venta_imp.php?f1=%s&f2=%s&modulos=%s&idEmpresa=%s','_self');",$valForm['txtFechaOrigen'],$valForm['txtFechaFinal'],$idModulos,$valForm['txtIdEmpresa']));
		}else
			$objResponse->alert("Debe Seleccionar minimo un modulo");
	}
	elseif(strtotime($valForm['txtFechaOrigen']) >= strtotime($valForm['txtFechaFinal'])){
		$objResponse->alert("Coloque una Fecha Final Mayor a la Inicial");
	}
		
	return $objResponse;
}

$xajax->register(XAJAX_FUNCTION,"buscarEmpresa");
$xajax->register(XAJAX_FUNCTION,"cargarModulos");
$xajax->register(XAJAX_FUNCTION,"validaEnvia");
?>