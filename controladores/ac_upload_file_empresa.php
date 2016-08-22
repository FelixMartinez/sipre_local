<?php
include("../connections/conex.php");

if ($_POST['accTipoImg'] == 1) {
	$rutaImg = editarImagen($_FILES['fleUrlGrupo'],"../upload/fotos_empresas/", "upload/fotos_empresas/","Grup_".str_replace(array(" ",",","."),"",$_POST['txtEmpresa']));
	
	// elimina del servidor la imagen anterior solo si existe
	/*if(file_exists($_POST['fleUrlGrupo']) && $_POST['hddIdArticulo'] == "")
		unlink($_POST['hddUrlImgGrupo']);*/
} else {
	$rutaImg = editarImagen($_FILES['fleUrlEmpresa'],"../upload/fotos_empresas/", "upload/fotos_empresas/","Emp_".str_replace(array(" ",",","."),"",$_POST['txtEmpresa']));
	
	// elimina del servidor la imagen anterior solo si existe
	/*if(file_exists($_POST['fleUrlEmpresa']) && $_POST['fleUrlEmpresa'] == "")
		unlink($_POST['hddUrlImgEmpresa']);*/
}
?>
<script>
if (<?php echo $_POST['accTipoImg']; ?> == 1) {
	window.parent.document.getElementById('imgGrupo').src = "<?php echo $rutaImg; ?>";
	window.parent.document.getElementById('hddUrlImgGrupo').value = "<?php echo $rutaImg; ?>";
} else {
	window.parent.document.getElementById('imgEmpresa').src = "<?php echo $rutaImg; ?>";
	window.parent.document.getElementById('hddUrlImgEmpresa').value = "<?php echo $rutaImg; ?>";
}
</script>