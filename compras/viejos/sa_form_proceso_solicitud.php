<div id="divFlotante" name="divFlotante" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0;">
	<div id="divFlotanteTitulo" class="handle"><table><tr><td id="tdFlotanteTitulo" name="tdFlotanteTitulo" width="100%"></td></tr></table></div>
    
<form id="frm" onsubmit="return false;" name="frm" style="margin:0">
    <table border="0" id="tblSeccion" width="350px">
    <tr>
    	<td>
        	<table width="100%">
            <tr>
            	<td align="right" class="tituloCampo" width="45%"><span class="textoRojoNegrita">*</span>C&oacute;digo de Empleado:</td>
                <td width="55%">
                	<input type="text" id="codigo_empleado" name="codigo_empleado" size="25"/>
				</td>
            </tr>
            </table>
			<table width="100%" id="condicionar_c" style="display:none;">
            <tr>
            	<td colspan="2" align="center" class="tituloCampo" width="50%">
				<label><input type="radio" id="cambiarestado" name="cambiarestado" value="4"/>Aprobar</label>
				<label><input type="radio" id="cambiarestado" name="cambiarestado" value="6"/>Condicionar</label>
				<label><input type="radio" id="cambiarestado" name="cambiarestado" value="7"/>Rechazar</label>
				</td>
               
            </tr>
            <tr>
            	<td align="right" class="tituloCampo" width="50%"><span class="textoRojoNegrita">*</span>Motivo Condicionamiento/Rechazo:</td>
                <td width="50%"><textarea  id="motivo_condicionamiento" name="motivo_condicionamiento" size="25"></textarea></td>
            </tr></table>
        </td>
    </tr>
    <tr>
    	<td align="right"><hr>
            <input type="text" id="id_solicitud_compra_f" name="id_solicitud_compra_f" />
            <input type="text" id="estado_f" name="estado_f" />
            <button type="submit" onclick="xajax_guardar(xajax.getFormValues('frm'));">Guardar</button>
            <button type="button" onclick="document.getElementById('divFlotante').style.display='none';">Cancelar</button>
        </td>
    </tr>
    </table>
</form>
</div>
<script language="javascript">
	var theHandle = document.getElementById("divFlotanteTitulo");
	var theRoot   = document.getElementById("divFlotante");
	Drag.init(theHandle, theRoot);
</script>