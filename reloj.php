<?php 
require_once("connections/conex.php");
?>
<script>
window.parent.document.getElementById('tdHoraSistema').innerHTML = "<?php echo date("h:ia"); ?>";
</script>