<?php
class PDF_Javascript extends FPDF {

    var $javascript;
    var $n_js;
	
	function __construct($orientation='P',$uni='mm',$format='Letter') {
		parent::__construct($orientation,$uni,$format);
	}
	function IncludeJS($script) {
        $this->javascript=$script;
    }
    function _putjavascript() {
        $this->_newobj();
        $this->n_js=$this->n;
        $this->_out('<<');
        $this->_out('/Names [(EmbeddedJS) '.($this->n+1).' 0 R ]');
        $this->_out('>>');
        $this->_out('endobj');
        $this->_newobj();
        $this->_out('<<');
        $this->_out('/S /JavaScript');
        $this->_out('/JS '.$this->_textstring($this->javascript));
        $this->_out('>>');
        $this->_out('endobj');
    }
    function _putresources() {
        parent::_putresources();
        if (!empty($this->javascript)) {
            $this->_putjavascript();
        }
    }
    function _putcatalog() {
        parent::_putcatalog();
        if (isset($this->javascript)) {
            $this->_out('/Names <</JavaScript '.($this->n_js).' 0 R>>');
        }
    }
}

class PDF_AutoPrint extends PDF_Javascript
{
	function __construct($orientation='P',$uni='mm',$format='Letter') {
		parent::__construct($orientation,$uni,$format);
	}
	function AutoPrint($dialog=false)
	{    
		$param=($dialog ? 'true' : 'false');
		$script="print(".$param.");";
		$this->IncludeJS($script);
	}
	
	function Header() {
		if ($this->mostrarHeader == 1) {
			if (strlen($this->logo_familia) > 5) {
				$this->Image($this->logo_familia,15,17,70);
			}
			
			$this->SetY(15);
			
			$this->SetTextColor(0,0,0);
			$this->SetFont('Arial','',5);
			$this->SetX(88);
			$this->Cell(200,9,$this->nombre_empresa,0,2,'L');
			
			if (strlen($this->rif) > 1) {
				$this->SetX(88);
				$this->Cell(200,9,$this->rif,0,2,'L');
			}
			
			if (strlen($this->direccion) > 1) {
				(strlen($this->telefono1) > 1) ? $arrayTelefono[] = $this->telefono1 : "";
				(strlen($this->telefono2) > 1) ? $arrayTelefono[] = $this->telefono2 : "";
				
				$this->SetX(88);
				$this->Cell(100,9,$this->direccion,0,2,'L');
			}
			
			(strlen($this->telefono1) > 1) ? $arrayTelefono[] = $this->telefono1 : "";
			(strlen($this->telefono2) > 1) ? $arrayTelefono[] = $this->telefono2 : "";
			if (isset($arrayTelefono)) {
				$this->SetX(88);
				$this->Cell(100,9,"Telf.: ".implode(" / ", $arrayTelefono),0,2,'L');
			}
			
			if (strlen($this->web) > 1) {
				$this->SetX(88);
				$this->Cell(200,9,utf8_encode($this->web),0,0,'L');
				$this->Ln();
			}
		}
	}
	
	//Page footer
	function Footer() {
		if ($this->mostrarFooter == 1) {
			if (strlen($this->nombreRegistrado) > 0) {
				$this->SetY(-22);
				$this->SetTextColor(0,0,0);
				$this->SetFont('Arial','I',6);
				$this->Cell(0,8,"Registrado por: ".$this->nombreRegistrado,0,0,'L');
			}
			
			$this->SetY(-22);
			$this->SetTextColor(0,0,0);
			$this->SetFont('Arial','I',6);
			$this->Cell(0,8,"Impreso".((strlen($this->nombreImpreso) > 0) ? " por: ".$this->nombreImpreso." el " : ": ").date("d-m-Y h:i a"),0,0,'R');
			
			$this->SetY(-22);
			$this->SetTextColor(0,0,0);
			$this->SetFont('Arial','I',8);
			$this->Cell(0,10,utf8_decode("Página ").$this->PageNo()."/{nb}",0,0,'C');
		}
	}
}
?>