<?php
require ('../xajax_core/xajax.inc.php');
$xajax = new xajax();
$xajax->configure('javascript URI', '../');


function buscar()
{
	$con=mysql_connect("localhost","root","mysqladmin");
	mysql_select_db("iutweb",$con);
	$query="select Cedula from usuario where Password='5678'";	
	$rs=mysql_query($query,$con);
	mysql_close($con);
	$row=mysql_fetch_object($rs);
	$objResponse = new xajaxResponse();
	$objResponse->assign('div1', 'innerHTML', '<h1>'.$row->Cedula.'</h1>');
	$objResponse->assign('div2', 'innerHTML', 'mundo');
	
	return $objResponse;
}


/*
	Section:  Register functions
	
	- <helloWorld>
	- <setColor>
*/
$reqBuscar =& $xajax->registerFunction('buscar');
//$reqHelloWorldMixed->setParameter(0, XAJAX_JS_VALUE, 0);

/*
	Section: processRequest
	
	This will detect an incoming xajax request, process it and exit.  If this is
	not a xajax request, then it is a request to load the initial contents of the page
	(HTML).
	
	Everything prior to this statement will be executed upon each request (whether it
	is for the initial page load or a xajax request.  Everything after this statement
	will be executed only when the page is first loaded.
*/
$xajax->processRequest();

echo '<?xml version="1.0" encoding="UTF-8"?>';
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
	<title>xajax example</title>
<?php
	// output the xajax javascript. This must be called between the head tags
	$xajax->printJavascript();
?>
</head>
<body style="text-align:center;">
	<div id="div1">&#160;</div>
	<br/>
	<div id="div2">&#160;</div><br/>
	
	<button onclick='<?php $reqBuscar->printScript(); ?>' >ver</button><marquee>Hola</marquee>
</body>
</html>