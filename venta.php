
<?php
 include "conexion/clase_sql.php";
include "clases/clase_interfaz.php";
$lc_inter = new interfaz();
/////////////////////////DATOS DE ENTRADA//////////////////////
$codRest = $_POST['codRest'];
$codUsuario = $_POST['codUsuario'];
$fecha = $_POST['fecha'];
list($dia, $mes, $anio) = explode("/", $fecha);
$fechaConsulta = $anio . $mes . $dia;


/////////////////////////VALIDACIONES//////////////////////
//FECHA
if (strlen($fecha) == "") {
    ?>
    <SCRIPT LANGUAGE="JavaScript">
        alert('Por favor ingrese una fecha');
	 window.history.back();
    </script>
    <?php
}
$lc_condicion[0] = $codRest;
$lc_condicion[1] = $fecha;
$lc_totalV = 0;
//CONFIGURACION DE CONEXION A DB
if ($lc_inter->validacionesGenerales('Configuracion_Pixel', $lc_condicion)) {
    $lc_rowdatos = $lc_inter->fn_leerobjeto();
    $lc_IP = $lc_rowdatos->IP_Ftp;
    $lc_Odbc = $lc_rowdatos->Nombre_ODBC;
    $lc_Rev_Center = $lc_rowdatos->Rev_Center;
    $lc_tipo_agrupacion = $lc_rowdatos->Tipo_Agrupacion;
    $lc_MulIva = $lc_rowdatos->mul_iva;
    $lc_DivIva = $lc_rowdatos->div_IVA;
    $lc_CodCadena = $lc_rowdatos->cod_cadena;
}
$lc_condicion_e[0] = $codRest;
$lc_condicion_e[1] = $fecha;
$mensj = 0;
//Verifica si mix, Cierrre de Caja, PyG, Inventario asentados
if ($lc_inter->validacionesGenerales('validaGeneracion', $lc_condicion_e)) {
    $lc_rowdatos = $lc_inter->fn_leerobjeto();
    $mensj = (string) $lc_rowdatos->ms;
}
echo ($mensj);
if (strlen($mensj) > 1) {?>
	<SCRIPT LANGUAGE="JavaScript">
       	alert('<?php echo $mensj; ?>');
	 	window.history.back();
	</script>
       <?php
} else { 
    /////////////////////////CONEXI�N A MICROS//////////////////////
    $datosConsulta = array(
        "codRest" => $codRest,
        "fecha" => $fecha,
        "codCadena" => $lc_CodCadena,
        "odbc"=>$lc_Odbc
    );
    $urlServicios = 'http://192.168.148.65/interfaz_22/ventaSir.php';
    $wsAuth = curl_init();
    curl_setopt($wsAuth, CURLOPT_URL, $urlServicios);
    curl_setopt($wsAuth, CURLOPT_FAILONERROR, true); 
    curl_setopt($wsAuth, CURLOPT_POST, 1);
    curl_setopt($wsAuth, CURLOPT_POSTFIELDS, http_build_query($datosConsulta));
    curl_setopt($wsAuth, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded'));
    curl_setopt($wsAuth, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($wsAuth, CURLOPT_SSL_VERIFYHOST, FALSE);
    curl_setopt($wsAuth, CURLOPT_SSL_VERIFYPEER, FALSE);
    $result = curl_exec($wsAuth);
    curl_close($wsAuth);


    $res = (json_decode($result));

    /////////////////////////CONSULTAR INFORMACI�N//////////////////////
   
    $jsXProducto = json_encode($res);
    
    //odbc_free_result($result);
    if ($jsXProducto != "null") {
        //VENTAS POR HORA
        

        $jsXHora = json_encode($res);
        //odbc_free_result($result);

        //CIERRE DE CAJAS
      

        $jsCC = json_encode($res);
        

        //VENTA POR CAJERO
        
        $jsXCajero = json_encode($res);
       

        //VENTAS CREDITOS SC
        
        $jsCreditoSC = json_encode($res);
        

        //TRANSACCIONES POR CAJERO
        
        $jsTransXCajero = json_encode($res);
  
        //TOTALES POR CAJERO
        
        $jsTotalXCajero = json_encode($res);
        
        //CAJEROS DELIVERY
        
        $jsDeliveryXCajero = json_encode($res);
        

        //CUPONES - GRUPON
        
        $jsCupones = json_encode($res);
        

        // VENTA Z
        
        $jsVentaZ = json_encode($res);
        
        // ANULACIONES
       
        $jsAnulada = json_encode($res);
        

        // HORAS TRABAJADAS POR CAJEROS
        
        $jsHoraCajeros = json_encode($res);
        

        //VENTA POR MEDIOS
       
        $jsVtaMedios = json_encode($res);
        

        /////////////////////////GENERAR INTERFAZ//////////////////////
        $lc_Cod_Mix_Cab = 0;
        $lc_condicion_e[2] = $codUsuario;
        if ($lc_inter->ingresarInformacion('CabeceraMix', $lc_condicion_e)) {
            $lc_rowdatos = $lc_inter->fn_leerobjeto();
            $lc_Cod_Mix_Cab = $lc_rowdatos->Cod_MixCab;
        }
        $lc_condicion_e[2] = $lc_Cod_Mix_Cab;
        $lc_condicion_e[3] = $jsXProducto;
        $lc_condicion_e[4] = $jsXHora;
        $lc_condicion_e[5] = $jsCC;
        $lc_condicion_e[6] = $jsXCajero;
        $lc_condicion_e[7] = $jsTransXCajero;
        $lc_condicion_e[8] = $jsCreditoSC;
        $lc_condicion_e[9] = $jsTotalXCajero;
        $lc_condicion_e[10] = $jsDeliveryXCajero;
        $lc_condicion_e[11] = $jsCupones;
        $lc_condicion_e[12] = $jsVentaZ;
        $lc_condicion_e[13] = $jsAnulada;
        $lc_condicion_e[14] = $jsHoraCajeros;
        $lc_condicion_e[16] = $jsVtaMedios;
       
        if ($lc_inter->ingresarInformacion('jsonInformacion', $lc_condicion_e)) {
            $lc_rowdatos = $lc_inter->fn_leerobjeto();
            $mensaje = $lc_rowdatos->mensaje;
            $total = $lc_rowdatos->total;
            $errores = isset($lc_rowdatos->errores);
            $ventaNeta = $lc_rowdatos->ventaNeta;
            $ventaBruta = $lc_rowdatos->ventaBruta;
            $iva = $lc_rowdatos->Iva;
            $servicios = $lc_rowdatos->Servicios;
            $ventaCero = $lc_rowdatos->VentaCero;
            $compensacion = $lc_rowdatos->Compensacion;
            $descuentos = $lc_rowdatos->descuentos;
            $codCierre = $lc_rowdatos->codCierre;
        }
    } else {
        ?>
		<SCRIPT LANGUAGE="JavaScript">
			alert('Es posible que su tienda se encuentre facturando con un Periodo Anterior. No existe informacion de ventas para el dia seleccionado');
			window.history.back();
		</script>
		<?php
}
    ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
        <title>Interface</title>
        <link rel="stylesheet" type="text/css" href="css/est_pantallas.css"/>
    </head>
    <body>
        <div id="respuesta" colspan="5" align="center" >
            <p class="titulo" id="titulo">&nbsp;</p>
            <p class="titulo">Interface de Ventas MICROS <?php if ($fecha != '') {
        echo 'del ' . $fecha;
    }
    ?></p>
        </div>
        <br/><br/>
		<table width="95%" border="0" cellpadding="0" cellspacing="0" align="center">
                    <tr>
                        <td valign="top" width="35%">
                            <div align="center">
                                <table width="90%" border="0" cellpadding="1" cellspacing="0">
                                    <tr>
                                        <td colspan="2" class="tabla_logeo solo_bordes"><div align="center"><strong>VENTAS POR PRODUCTO</strong></div></td>
                                    </tr>
                                    <tr>
                                        <td colspan="2" class="tabla_cabecera solo_bordes"><div align="center"><strong>Reporte de Errores</strong></div></td>
                                    </tr>
                                    <tr class='tabla_detalle'><td colspan=2 class='solo_bordes'><?php echo $mensaje; ?></td></tr>
                                    <tr><td width='60%' class='tabla_cabecera solo_bordes'><div align='left'><strong>Total Errores Encontrados:</strong></div></td>
                                        <td class='tabla_detalle solo_bordes'><?php echo $errores; ?></td>
                                    </tr>
                                    <tr><td width='60%' class='tabla_cabecera solo_bordes'><div align='left'><strong>Total Venta Producto:</strong></div></td>
                                        <td class='tabla_detalle solo_bordes'><?php echo round($total, 2); ?></td>
                                    </tr>
                                    <tr><td width='60%' class='tabla_cabecera solo_bordes'><div align='left'><strong>Total Descuentos:</strong></div></td>
                                        <td class='tabla_detalle solo_bordes'> <?php if ($descuentos > 0) {
        echo '-' . round($descuentos, 2);
    } else {
        echo 0;
    }
    ?>
					     </td>
                                    </tr>
                                    <tr><td width='60%' class='tabla_cabecera solo_bordes'><div align='left'><strong>Total Servicios:</strong></div></td>
                                        <td class='tabla_detalle solo_bordes'> <?php echo round($servicios, 2); ?></td>
                                    </tr>
                                    <tr><td width='60%' class='tabla_cabecera solo_bordes'><div align='left'><strong>Total Venta $:</strong></div></td>
                                        <td class='tabla_detalle solo_bordes'><?php echo round($total - $descuentos + $servicios, 2); ?></td>
                                    </tr>

                                    <tr>
                                        <td colspan="2" class="separador2">&nbsp;</td>
                                    </tr>
                                    <tr>
                                        <td>&nbsp;</td>
                                    </tr>
                                    <tr>
                                        <td colspan="2" class="tabla_logeo solo_bordes"><div align="center"><strong>VENTAS POR CAJERO</strong></div></td>
                                    </tr>
                                    <?php
$venta = 0;
    $numTransaccion = 0;
    $codCaja = 0;
    $totalVenta = 0;
    $totalTransaccion = 0;
    $lc_condicion_e[2] = $codCierre;
    if ($lc_inter->consultas('ventaCajero', $lc_condicion_e)) {
        while ($lc_rowdatosTotales = $lc_inter->fn_leerobjeto()) {
            $venta = $lc_rowdatosTotales->Valor;
            $numTransaccion = $lc_rowdatosTotales->Num_Trans;
            $codCaja = $lc_rowdatosTotales->Cod_Cajero;
            $totalVenta += $venta;
            $totalTransaccion += $numTransaccion;
            ?>
                                            <tr><td colspan=2 class='tabla_cabecera solo_bordes'><strong><?php echo $codCaja ?></strong></td>
                                            </tr>
                                            <tr><td width='60%' class='tabla_cabecera solo_bordes'><div align='left'><strong>Numero de Transacciones:</strong></div></td>
                                                <td class='tabla_detalle solo_bordes'><?php echo $numTransaccion ?></td>
                                            </tr>
                                            <tr><td width='40%' class='tabla_cabecera solo_bordes'><div align='left'><strong>Venta:</strong></div></td>
                                                <td class='tabla_detalle solo_bordes'><?php echo round($venta, 2) ?></td>
                                            </tr>
                                            <tr><td colspan="2" class="separador2">&nbsp;</td></tr>
                                            <?php
}
    }
    ?>
                                    <tr>
                                        <td colspan="2" class='tabla_logeo solo_bordes'><strong>Totales</strong></td>
                                    </tr>
                                    <tr>
                                        <td width="60%" class='tabla_cabecera solo_bordes'><div align="left"><strong>Total Venta $:</strong></div></td>
                                        <td width="40%" class="tabla_detalle solo_bordes"><?php echo round($totalVenta, 2); ?></td>
                                    </tr>
                                    <tr>
                                        <td width="60%" class='tabla_cabecera solo_bordes'>
                                            <div align="left"><strong>Total Transacciones $:</strong></div>
                                        </td>
                                        <td width="40%" class="tabla_detalle solo_bordes"><?php echo $totalTransaccion; ?></td>
                                    </tr>
                                    <tr><td colspan="2" class="separador2">&nbsp;</td></tr>
                                </table>
                            </div>
                        </td>
                        <td valign="top" width="30%">
                            <div align="center">
                                <table width="90%" border="0" cellpadding="1" cellspacing="0" align="center">
                                    <tr>
                                        <td colspan="2" valign="top" class='tabla_logeo solo_bordes'><div align="center"><strong>VENTAS POR DINERO</strong></div></td>
                                    </tr>
                                    <tr><td width='65%' class='tabla_cabecera solo_bordes'><strong>Ventas Netas:</strong></td>
                                        <td class='tabla_detalle solo_bordes'> <?php echo round($ventaNeta, 2) ?></td>
                                    </tr>
                                    <tr><td width='65%' class='tabla_cabecera solo_bordes'><strong>Base Imponible IVA</strong></td>
                                        <td class='tabla_detalle solo_bordes'> <?php echo round($ventaNeta - $ventaCero, 2) ?></td>
                                    </tr>
                                    <tr><td width='65%' class='tabla_cabecera solo_bordes'><strong>Base Imponible No IVA</strong></td>
                                        <td class='tabla_detalle solo_bordes'>  <?php echo round($ventaCero, 2) ?> </td>
                                    </tr>
                                    <tr><td width='65%' class='tabla_cabecera solo_bordes'><strong>IVA</strong></td>
                                        <td class='tabla_detalle solo_bordes'> <?php echo round($iva, 2) ?> </td>
                                    </tr>
                                    <tr><td width='65%' class='tabla_cabecera solo_bordes'><strong>Descuentos</strong></td>
                                        <td class='tabla_detalle solo_bordes'><?php if ($descuentos > 0) {
        echo '-' . round($descuentos, 2);
    } else {
        echo 0;
    }
    ?></td>
                                    </tr>
                                    <tr><td width='65%' class='tabla_cabecera solo_bordes'><strong>Servicio</strong></td>
                                        <td class='tabla_detalle solo_bordes'> <?php echo round($servicios, 2) ?>  </td>
                                    </tr>
                                    <tr><td width='65%' class='tabla_cabecera solo_bordes'><strong>Venta Bruta</strong></td>
                                        <td class='tabla_detalle solo_bordes'> <?php echo round($ventaBruta, 2) ?>  </td>
                                    </tr>
                                    <tr><td width='65%' class='tabla_cabecera solo_bordes'><strong>Compensaci&oacute;n</strong></td>
                                        <td class='tabla_detalle solo_bordes'> <?php echo round($compensacion, 2) ?>  </td>
                                    </tr>
                                    <tr><td colspan='2' class='separador2'>&nbsp;</td></tr>
                                </table>
                            </div>
                        </td>
                        <td valign="top" width="35%">
                            <div align="center">
                                <table width="90%" border="0" cellpadding="1" cellspacing="0" align="center">
                                    <tr>
                                        <td colspan="3" valign="top" class='tabla_logeo solo_bordes'><div align="center"><strong>VENTAS POR HORA</strong></div></td>
                                    </tr>
                                    <tr>
                                        <td width="28%" class='tabla_cabecera solo_bordes'><strong>Tiempo</strong></td>
                                        <td width="33%" class='tabla_cabecera solo_bordes'><strong>Valor</strong></td>
                                        <td width="39%" class='tabla_cabecera solo_bordes'><strong>Transacciones</strong></td>
                                    </tr>
                                    <?php
$totalVentaH = 0;
    $totalTransaccionH = 0;
    if ($lc_inter->consultas('ventasHora', $lc_condicion_e)) {
        while ($lc_rowHora = $lc_inter->fn_leerobjeto()) {
            $hora = $lc_rowHora->Tiempo;
            $venta = $lc_rowHora->Valor;
            $num_tran = $lc_rowHora->Transacciones;
            $totalVentaH += $venta;
            $totalTransaccionH += $num_tran;
            ?>
                                            <tr>
                                                <td  width='28%' class='tabla_cabecera solo_bordes'><strong><?php echo $hora; ?></strong></td>
                                                <td class='tabla_detalle solo_bordes'><?php echo $venta; ?></td>
                                                <td class='tabla_detalle solo_bordes'><?php echo $num_tran; ?></td>
                                            </tr>
                                            <?php
}
    }
    ?>
                                    <tr><td colspan="3" class="separador2">&nbsp;</td></tr>
                                    <tr>
                                        <td  width='28%' class='tabla_logeo solo_bordes' align="center"><strong>TOTALES:</strong></td>
                                        <td  width='33%' class='tabla_cabecera solo_bordes'><strong>&nbsp;<?php echo $totalVentaH; ?></strong></td>
                                        <td  width='39%' class='tabla_cabecera solo_bordes'><strong>&nbsp;<?php echo $totalTransaccionH; ?></strong></td>
                                    </tr>
                                </table>
                            </div>
                        </td>
                    </tr>
                </table>
        <?php
//}
}
?>
