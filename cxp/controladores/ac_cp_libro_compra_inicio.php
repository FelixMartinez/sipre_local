<?php


function cargarModulos(){
	$objResponse = new xajaxResponse();
	
	$queryModulos = sprintf("SELECT * FROM pg_modulos");
	$rsModulos = mysql_query($queryModulos);
	if (!$rsModulos) return $objResponse->alert(mysql_error()."\n\nLINE: ".__LINE__);
	$html = "<table border=\"0\" width=\"100%\">";
	while ($rowModulos = mysql_fetch_array($rsModulos)) {
		$contFila++;
		
		$html .= (fmod($contFila, 4) == 1) ? "<tr align=\"center\" height=\"22\">" : "";
		
			$html .= sprintf("<td><label><input type=\"checkbox\" id=\"cbxModulo\" name=\"cbxModulo[]\" checked=\"checked\" value=\"%s\"/>%s</label></td>",
				$rowModulos['id_modulo'],
				$rowModulos['descripcionModulo']);
		
		$html .= (fmod($contFila, 4) == 0) ? "</tr>" : "";
	}
	$html .= "</table>";
	
	$objResponse->assign("tdModulos","innerHTML",$html);
	
	return $objResponse;
}

function validaEnvia($frmFechasLibros){
	$objResponse = new xajaxResponse();
	
	if (isset($frmFechasLibros['cbxModulo'])) {
		foreach ($frmFechasLibros['cbxModulo'] as $pos => $valor) {
			$idModulos .= sprintf("%s,",$valor);
		}
		$idModulos = substr($idModulos, 0, (strlen($idModulos)-1));
	}
	
	if ($frmFechasLibros['txtFechaOrigen'] == "" || $frmFechasLibros['txtFechaFinal'] == "") {
		$objResponse->alert("Por Favor Coloque el Rango de Fechas");
	} else if (!($frmFechasLibros['lstFormatoNumero'] > 0)) {
		$objResponse->script("byId('lstFormatoNumero').className = 'inputErrado';");
		$objResponse->alert("Por Favor seleccione un formato de número");
	} else if ($frmFechasLibros['txtFechaOrigen'] <= $frmFechasLibros['txtFechaFinal']) {
		if ($idModulos != ''){
			$objResponse->script(sprintf("window.open('cp_libro_compra_imp.php?f1=%s&f2=%s&modulos=%s&lstFormatoNumero=%s','_self');",
				$frmFechasLibros['txtFechaOrigen'],
				$frmFechasLibros['txtFechaFinal'],
				$idModulos,
				$frmFechasLibros['lstFormatoNumero']));
		} else
			$objResponse->alert("Seleccione al menos un módulo.");
	} else if ($frmFechasLibros['txtFechaOrigen'] == "" || $frmFechasLibros['txtFechaFinal'] == "") {
		$objResponse->alert("Por Favor Coloque el Rango de Fechas");
	} else if ($frmFechasLibros['txtFechaOrigen'] >= $frmFechasLibros['txtFechaFinal']) {
		$objResponse->alert("Coloque una Fecha Final Mayor a la Inicial");
	}
	
	return $objResponse;
}

$xajax->register(XAJAX_FUNCTION,"cargarModulos");
$xajax->register(XAJAX_FUNCTION,"validaEnvia");
?>