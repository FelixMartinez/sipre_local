<?php

require_once ("../connections/conex.php");

session_start();

function actualizarOrdenServicio($idOrden) {
    
        $queryConfig403 = sprintf("SELECT * FROM pg_configuracion_empresa config_emp
                INNER JOIN pg_configuracion config ON (config_emp.id_configuracion = config.id_configuracion)
        WHERE config.id_configuracion = 403 AND config_emp.status = 1 AND config_emp.id_empresa = (SELECT id_empresa FROM sa_orden WHERE id_orden = %s LIMIT 1);",
                valTpDato($idOrden, "int"));
        $rsConfig403 = mysql_query($queryConfig403);
        if (!$rsConfig403) { return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
        
        $rowConfig403 = mysql_fetch_assoc($rsConfig403);
        if($rowConfig403['valor'] == "3"){//puerto rico
        
            // RECALCULA LOS MONTOS DE LA ORDEN
            $sqlDetOrden = sprintf("SELECT SUM(precio_unitario * cantidad) AS valor FROM sa_det_orden_articulo
            WHERE id_orden = %s
                    AND aprobado = 1
                    AND estado_articulo <> 'DEVUELTO'
                    AND (SELECT COUNT(*) FROM sa_det_orden_articulo_iva 
                            WHERE sa_det_orden_articulo_iva.id_det_orden_articulo = sa_det_orden_articulo.id_det_orden_articulo
                        ) > 0;",
                    $idOrden);
            $rsDetOrden = mysql_query($sqlDetOrden);
            if (!$rsDetOrden) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__));
            $rowDetOrden = mysql_fetch_array($rsDetOrden);
            $valorBaseimponibleRep = $rowDetOrden['valor'];

            $sqlDetRepuestosIvas = sprintf("SELECT SUM(precio_unitario * cantidad) AS base_imponible, sa_det_orden_articulo_iva.id_iva
            FROM sa_det_orden_articulo
            INNER JOIN sa_det_orden_articulo_iva ON sa_det_orden_articulo.id_det_orden_articulo = sa_det_orden_articulo_iva.id_det_orden_articulo
            WHERE id_orden = %s
                    AND aprobado = 1
                    AND estado_articulo <> 'DEVUELTO'
                    GROUP BY sa_det_orden_articulo_iva.id_iva",
                    $idOrden);
            $rsDetRepuestosIvas = mysql_query($sqlDetRepuestosIvas);
            if (!$rsDetRepuestosIvas) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__));

            while($rowDetRepuestosIvas = mysql_fetch_assoc($rsDetRepuestosIvas)){
                $arrayIvasArticulos[$rowDetRepuestosIvas["id_iva"]] = $rowDetRepuestosIvas["base_imponible"];
            }


            $sqlDetOrdenExento = sprintf("SELECT SUM(precio_unitario * cantidad) AS valorExento FROM sa_det_orden_articulo
            WHERE id_orden = %s
                    AND aprobado = 1
                    AND estado_articulo <> 'DEVUELTO'
                    AND (SELECT COUNT(*) FROM sa_det_orden_articulo_iva 
                            WHERE sa_det_orden_articulo_iva.id_det_orden_articulo = sa_det_orden_articulo.id_det_orden_articulo
                        ) = 0;",
                    $idOrden);
            $rsDetOrdenExento = mysql_query($sqlDetOrdenExento);
            if (!$rsDetOrdenExento) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__));
            $rowDetOrdenExento = mysql_fetch_array($rsDetOrdenExento);
            $valorExentoRep = $rowDetOrdenExento['valorExento'];


            $sqlTemp = sprintf("SELECT
            (CASE id_modo
                    WHEN 1 THEN
                            SUM((precio_tempario_tipo_orden * ut) / base_ut_precio)
                    WHEN 2 THEN
                            SUM(precio)
            END) AS valorTemp
            FROM sa_det_orden_tempario
            WHERE id_orden = %s AND estado_tempario <> 'DEVUELTO';",
                    $idOrden);
            $rsTemp = mysql_query($sqlTemp);
            if (!$rsTemp) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__));
            $rowTemp = mysql_fetch_array($rsTemp);
            $valorTemp = $rowTemp['valorTemp'];

            $sqlTOT = sprintf("SELECT
                    orden.id_orden,
                    orden.id_tipo_orden,
                    SUM(orden_tot.monto_subtotal) AS monto_subtotalTot
            FROM sa_orden_tot orden_tot
                    INNER JOIN sa_orden orden ON (orden_tot.id_orden_servicio = orden.id_orden)
            WHERE orden.id_orden = %s
            GROUP BY orden.id_orden, orden.id_tipo_orden;",
                    $idOrden);
            $rsTOT = mysql_query($sqlTOT);
            if (!$rsTOT) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__));
            $rowTOT = mysql_fetch_array($rsTOT);

            $queryPorcentajeTot = sprintf("SELECT *
            FROM sa_det_orden_tot
            WHERE id_orden = %s;",
                    valTpDato($idOrden, "int"));
            $rsPorcentajeTot = mysql_query($queryPorcentajeTot);
            if (!$rsPorcentajeTot) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__));
            $rowPorcentajeTot = mysql_fetch_assoc($rsPorcentajeTot);

            $valorDetTOT = $rowTOT['monto_subtotalTot'] + (($rowPorcentajeTot['porcentaje_tot'] * $rowTOT['monto_subtotalTot']) / 100);

            $sqlNota = sprintf("SELECT SUM(precio) AS precio FROM sa_det_orden_notas
            WHERE id_orden = %s 
            AND aprobado = 1;",
                    $idOrden);
            $rsNota = mysql_query($sqlNota);
            if (!$rsNota) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__));
            $rowNota = mysql_fetch_array($rsNota);

            $sqlDesc = sprintf("SELECT porcentaje_descuento FROM sa_orden
            WHERE id_orden = %s;",
                    $idOrden);
            $rsDesc = mysql_query($sqlDesc);
            if (!$rsDesc) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__));
            $rowDesc = mysql_fetch_array($rsDesc);

            $sqlIvas = sprintf("SELECT base_imponible, subtotal_iva, id_iva, iva
            FROM sa_orden_iva
            WHERE id_orden = %s;",
                    $idOrden);
            $rsIvas = mysql_query($sqlIvas);
            if (!$rsIvas) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__));

            $tieneIva = mysql_num_rows($rsIvas);

            $totalExento = 0;//total exento de la orden
            $totalBaseImponibleItems = 0;//total base imponible de items que no sean repuestos

            if($tieneIva){//si tiene iva, separar exentos de repuestos
                $totalExento += $valorExentoRep;
                $totalBaseImponibleItems += $rowNota['precio'] + $valorDetTOT + $valorTemp;
            }else{//si no, todo se va al exento
                $totalExento += $rowNota['precio'] + $valorDetTOT + $valorTemp + $valorExentoRep;
            }

            while($rowIvas = mysql_fetch_assoc($rsIvas)){//busco ivas y porcentajes de la orden
                $arrayIvasOrden[$rowIvas["id_iva"]] = $rowIvas["iva"];
            }

            $totalIva = 0;
            foreach($arrayIvasOrden as $idIva => $porcIva){//recorro ivas de la orden e ivas de los repuestos
                $baseIva = $totalBaseImponibleItems + $arrayIvasArticulos[$idIva];//sumo base items + base articulos que aplican            
                $baseIvaDesc = $baseIva - round(($baseIva*($rowDesc['porcentaje_descuento']/100)),2);            
                $ivaSubTotal = $baseIvaDesc*($porcIva/100);            
                $totalIva += $ivaSubTotal;

echo "baseiva:".$baseIva;
echo "<br>";
echo "baseivadesc".$baseIvaDesc;
echo "<br>";
echo "ivasubtotal".$ivaSubTotal;
echo "<br>";
echo "totaliva".$totalIva;
echo "<br>";

//                $sqlUpdateIva = sprintf("UPDATE sa_orden_iva SET base_imponible = %s, subtotal_iva = %s
//                                        WHERE id_orden = %s 
//                                        AND id_iva = %s;",
//                                valTpDato($baseIvaDesc, "double"),
//                                valTpDato($ivaSubTotal, "double"),
//                                $idOrden,
//                                $idIva);
//                $rsUpdateIva = mysql_query($sqlUpdateIva);
//                if (!$rsUpdateIva) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__));            
            }

            $subtotal = $totalBaseImponibleItems + $totalExento + $valorBaseimponibleRep;

            $Desc = round($subtotal * ($rowDesc['porcentaje_descuento']/100),2);
            $totalExento = round($totalExento - ($totalExento*($rowDesc['porcentaje_descuento']/100)),2);
            $totalOrden = round($subtotal - $Desc + $totalIva,2);
//            $updateSQL = "UPDATE sa_orden SET
//                    subtotal = ".valTpDato($subtotal, "double").",
//                    monto_exento = ".valTpDato($totalExento, "double").",
//                    subtotal_iva = ".valTpDato($totalIva, "double").",
//                    subtotal_descuento = ".valTpDato($Desc, "double").",
//                    total_orden = ".valTpDato($totalOrden, "double")."
//            WHERE id_orden = ".$idOrden;
//            $Result1 = mysql_query($updateSQL);
//            if (!$Result1) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__));
	
            return array(false, "Prueba de totales\nSubtotal".$subtotal."\nTotalExento".$totalExento."\ntotalIva:".$totalIva."\nSubtotal Desc.".round($Desc,2)."\n\nTOTAL ORDEN:".($totalOrden));
            
        }else{//vzla panama
        //
        //
        //
        //
            // RECALCULA LOS MONTOS DE LA ORDEN
            $sqlDetOrden = sprintf("SELECT SUM(precio_unitario * cantidad) AS valor FROM sa_det_orden_articulo
            WHERE id_orden = %s
                    AND aprobado = 1
                    AND estado_articulo <> 'DEVUELTO';",
                    $idOrden);
            $rsDetOrden = mysql_query($sqlDetOrden);
            if (!$rsDetOrden) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__));
            $rowDetOrden = mysql_fetch_array($rsDetOrden);
            $valor = $rowDetOrden['valor'];

            $sqlDesc = sprintf("SELECT * FROM sa_orden
            WHERE id_orden = %s;",
                    $idOrden);
            $rsDesc = mysql_query($sqlDesc);
            if (!$rsDesc) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__));
            $rowDesc = mysql_fetch_array($rsDesc);

            $Desc = ($rowDesc['porcentaje_descuento'] * $valor) / 100;
            $valorConDesc = $valor - $Desc;

            $sqlTemp = sprintf("SELECT
            (CASE id_modo
                    WHEN 1 THEN
                            SUM((precio_tempario_tipo_orden * ut) / base_ut_precio)
                    WHEN 2 THEN
                            SUM(precio)
            END) AS valorTemp
            FROM sa_det_orden_tempario
            WHERE id_orden = %s AND estado_tempario <> 'DEVUELTO';",
                    $idOrden);
            $rsTemp = mysql_query($sqlTemp);
            if (!$rsTemp) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__));
            $rowTemp = mysql_fetch_array($rsTemp);
            $valorTemp = $rowTemp['valorTemp'];

            $sqlTOT = sprintf("SELECT
                    orden.id_orden,
                    orden.id_tipo_orden,
                    SUM(orden_tot.monto_subtotal) AS monto_subtotalTot
            FROM sa_orden_tot orden_tot
                    INNER JOIN sa_orden orden ON (orden_tot.id_orden_servicio = orden.id_orden)
            WHERE orden.id_orden = %s
            GROUP BY orden.id_orden, orden.id_tipo_orden;",
                    $idOrden);
            $rsTOT = mysql_query($sqlTOT);
            if (!$rsTOT) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__));
            $rowTOT = mysql_fetch_array($rsTOT);

            $queryPorcentajeTot = sprintf("SELECT *
            FROM sa_det_orden_tot
            WHERE id_orden = %s;",
                    valTpDato($idOrden, "int"));
            $rsPorcentajeTot = mysql_query($queryPorcentajeTot);
            if (!$rsPorcentajeTot) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__));
            $rowPorcentajeTot = mysql_fetch_assoc($rsPorcentajeTot);

            $valorDetTOT = $rowTOT['monto_subtotalTot'] + (($rowPorcentajeTot['porcentaje_tot'] * $rowTOT['monto_subtotalTot']) / 100);

            $sqlNota = sprintf("SELECT SUM(precio) AS precio FROM sa_det_orden_notas
            WHERE id_orden = %s 
            AND aprobado = 1;",
                    $idOrden);
            $rsNota = mysql_query($sqlNota);
            if (!$rsNota) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__));
            $rowNota = mysql_fetch_array($rsNota);

            $totalConDesc = $rowNota['precio'] + $valorDetTOT + $valorTemp + $valorConDesc;
            $totalSinDesc = $rowNota['precio'] + $valorDetTOT + $valorTemp + $valor;
            $totalIva = ($totalConDesc * $rowDesc['iva'])/100;
//
//            $updateSQL = "UPDATE sa_orden SET
//                    subtotal = ".valTpDato($totalSinDesc, "double").",
//                    base_imponible = ".valTpDato($totalConDesc, "double").",
//                    idIva = ".valTpDato($rowDesc['idIva'], "int").",
//                    iva = ".valTpDato($rowDesc['iva'], "double").",
//                    subtotal_iva = ".valTpDato($totalIva, "double").",
//                    subtotal_descuento = ".valTpDato($Desc, "double")."
//            WHERE id_orden = ".$idOrden;
//            $Result1 = mysql_query($updateSQL);
//            if (!$Result1) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__));		
	
        }
            
	return array(true, "");
}

var_dump(actualizarOrdenServicio(1));