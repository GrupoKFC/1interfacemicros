
<?php
include("../conexion/clase_sql.php");
include("../clases/clase_interfaz.php");
$lc_inter = new interfaz();
/////////////////////////DATOS DE ENTRADA//////////////////////
$codRest = $_GET['codRest'];
$reporte = $_GET['reporte'];
$fechaIn = $_GET['fechaIn'];
$fechaFn = $_GET['fechaFn'];

list($diaIn, $mesIn, $anioIn) = explode("/", $fechaIn);
list($diaFn, $mesFn, $anioFn) = explode("/", $fechaFn);
$fechaInConsulta = $anioIn . $mesIn . $diaIn;
$fechaFnConsulta = $anioFn . $mesFn . $diaFn;

/////////////////////////VALIDACIONES//////////////////////
$lc_condicion[0] = $codRest;
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

/////////////////////////CONEXIÓN A MICROS//////////////////////
$server = $lc_Odbc;
$user   = 'dba';
$pwd    = 'micros3700';
$db     = 'micros';
$lc_regs;
$dbh=@sybase_connect($server, $user, $pwd) or die('Cannot connect');

if ($reporte == 'CuadreCajaConsolidado') {

	//DATOS FORMAS PAGO
	$queryFormasPago = "
		SELECT	DISTINCT cnt.cm_tender_name 		  AS FormaPago,
			SUM(ctd.base_count_amt)			  AS Contado, 
			SUM(cnt.cm_system)			  AS Sistema , 
			SUM(ctd.base_count_amt - cnt.cm_system)	  AS Diferencia, 
			CONVERT(CHAR(12), cnt.business_date, 112) AS Fecha 
		FROM 	custom.vcm_count_diff		AS cnt
		LEFT JOIN micros.vcm_counted_unit_dtl 	AS ctd ON cnt.receptacle_seq = ctd.receptacle_seq and cnt.cm_item_seq = ctd.cm_item_seq
		LEFT JOIN micros.cm_receptacle_dtl 	AS rcp ON cnt.receptacle_seq = rcp.receptacle_seq
		LEFT JOIN micros.emp_def 		AS em1 ON em1.emp_seq = cnt.employee_seq
		LEFT JOIN micros.emp_def 		AS em2 ON em2.emp_seq = cnt.count_emp_seq
		WHERE	cnt.business_date BETWEEN '$fechaInConsulta' AND '$fechaFnConsulta'
		GROUP BY Fecha, FormaPago
		ORDER BY Fecha, FormaPago";
	//VALORES CAJA FUERTE
	$queryCaja = "
		SELECT	SUM(isnull(base_item_amt,0)) 	AS CajaFuerte 
		FROM   micros.cm_receptacle_dtl		AS rec
		INNER JOIN micros.cm_transaction_dtl 	AS trn ON rec.receptacle_seq = trn.transaction_receptacle_seq
		WHERE rec.receptacle_state = 0 AND rec.receptacle_type = 1";

	//CAJEROS
	$queryCajero = "
		SELECT	DISTINCT em2.last_name 	AS Apellido, 
			em2.first_name		AS Nombre
		FROM	custom.vcm_count_diff 	AS cnt
		INNER JOIN micros.emp_def 	AS em2 ON em2.emp_seq = cnt.count_emp_seq
		WHERE cnt.business_date BETWEEN '$fechaInConsulta' AND '$fechaFnConsulta'";

	/////////////////////////CONSULTAR INFORMACIÓN//////////////////////
	//DATOS FORMAS PAGO
	$result = sybase_query($queryFormasPago, $dbh, false) or die('error');
	$i = 0;
	while($row = sybase_fetch_object($result)) { 
		$lc_regs ['Detalle'][$i]= array(
			"FormaPago"	=> $row -> FormaPago,
			"Contado"	=> round($row -> Contado, 0),
			"Sistema"	=> round($row -> Sistema, 0),
			"Diferencia"	=> round($row -> Diferencia,0),
			"Fecha"		=> $row -> Fecha,
		);
		$i++;
	}

	sybase_free_result ($result);
	//VALORES CAJA FUERTE
	$result = sybase_query($queryCaja, $dbh, false) or die('error');
	$i = 0;
	while($row = sybase_fetch_object($result)) { 

		$lc_regs ['CajaFuerte'][$i]= array(
			"CajaFuerte"	=>  round($row -> CajaFuerte, 0)
		);
		$i++;
	}

	//CAJERO
	$result = sybase_query($queryCajero, $dbh, false) or die('error');
	$i = 0;
	while($row = sybase_fetch_object($result)) { 
		$lc_regs ['Cajero'][$i]= array(
			"Apellido"	=> utf8_encode($row -> Apellido),
			"Nombre" 	=> utf8_encode($row -> Nombre),
		);
		$i++;
	}
	$json = json_encode($lc_regs);
	print_r($json);	
} elseif ($reporte == 'BoletaPorEmpleado') {
	//BOLETA POR EMPLEADO
	$queryBoletaXEmpleado = "
		SELECT  CONVERT(VARCHAR(20), invoicenum)    	AS Boleta,
        		CONVERT(VARCHAR(20), checknum)      	AS CheckMicros,
		        CASE
		            WHEN invstatus = 1 THEN 'Activa'
		            WHEN invstatus = 0 THEN 'Nula'
		        END                                 	AS Estado, 
			invstatus 				as cod,
		        donationamount                      	AS Donacion,
		        total                               	AS Total,
		        emp_name                            	AS Nombre,
		        CONVERT(VARCHAR(11),businessdate,103) 	AS Fecha
		FROM    custom.fiscaldocsemp
		WHERE	businessdate BETWEEN '$fechaInConsulta' AND '$fechaFnConsulta'
		ORDER BY Fecha, Nombre";

	/////////////////////////CONSULTAR INFORMACIÓN//////////////////////
	//BOLETA POR EMPLEADO
	$result = sybase_query($queryBoletaXEmpleado, $dbh, false) or die('error');
	$i = 0;
	while($row = sybase_fetch_object($result)) { 
		$lc_regs ['Detalle'][$i]= array(
			"Boleta"	=> $row -> Boleta,
			"CheckMicros"	=> $row -> CheckMicros,
			"Estado"	=> $row -> Estado,
			"Donacion"	=> round($row -> Donacion, 2),
			"Total"		=> round($row -> Total,2),
			"Nombre"	=> utf8_encode($row -> Nombre),
			"Fecha"		=> $row -> Fecha,
		);
		$i++;
	}
	sybase_free_result ($result);
	$json = json_encode($lc_regs);
	print_r($json);	
}elseif ($reporte == 'BoletaPorEmpleado2') {
	//BOLETA POR EMPLEADO
	$queryBoletaXEmpleado = "
		SELECT  CONVERT(VARCHAR(20), invoicenum)    	AS Boleta,
        		CONVERT(VARCHAR(20), checknum)      	AS CheckMicros,
		        CASE
		            WHEN invstatus = 1 THEN 'Activa'
		            WHEN invstatus = 2 THEN 'Nula'
		        END                                 	AS Estado, 
		        donationamount                      	AS Donacion,
		        total                               	AS Total,
		        emp_name                            	AS Nombre,
		        CONVERT(VARCHAR(11),businessdate,103) 	AS Fecha
		FROM    custom.fiscaldocsemp
		WHERE	businessdate BETWEEN '$fechaInConsulta' AND '$fechaFnConsulta'
		ORDER BY Fecha, Nombre";

	/////////////////////////CONSULTAR INFORMACIÓN//////////////////////
	//BOLETA POR EMPLEADO
	$result = sybase_query($queryBoletaXEmpleado, $dbh, false) or die('error');
	$i = 0;
	$j = 0;
	$nombre = '';
	$fecha = '';
print_r($result);
	while($row = sybase_fetch_object($result)) { 
		if ($nombre != $row -> Nombre){
			$j = 0;
			$i++;
			$lc_regs []= array(
				"Nombre"	=> $row -> Nombre,
				"Fecha"		=> $row -> Fecha,
			);
			$nombre = $row -> Nombre;
			$fecha = $row -> Fecha;
		}
		$lc_regs []['Detalle'][]= array(
			"Boleta"	=> $row -> Boleta,
			"CheckMicros"	=> $row -> CheckMicros,
			"Estado"	=> $row -> Estado,
			"Donacion"	=> round($row -> Donacion, 0),
			"Total"		=> round($row -> Total,0)
		);
$j++;
	}
	sybase_free_result ($result);
	$json = json_encode($lc_regs);
	print_r($json);	
} elseif ($reporte == 'CuadreCaja') {
	//DATOS FORMAS PAGO
	$queryFormasPago = "
		SELECT  CONVERT(CHAR(12), rcp.open_business_date, 103) AS Fecha, case
when dtl.ob_adjustment = 'F' then dff.cm_tender_name
else 'Ajuste ' + dff.cm_tender_name
end as Concepto,
        dtl.base_count_amt   AS Contado,
       CASE 
        WHEN dtl.ob_adjustment = 'T' THEN 0
        ELSE dff.cm_system
       END                  AS Sistema, 
       Contado - Sistema    AS Diferencia,
em1.last_name + ' ' + em1.first_name + ' - ' + em1.payroll_id AS Empleado,
em2.last_name + ' ' + em2.first_name + ' - ' + em2.payroll_id AS ContadoPor
FROM    micros.cm_receptacle_dtl        AS rcp
INNER JOIN custom.vcm_receptacle_resume AS rsm ON rcp.receptacle_seq  = rsm.receptacle_seq 
INNER JOIN custom.vcm_count_diff        AS dff ON rcp.receptacle_seq  = dff.receptacle_seq 
inner join micros.vcm_counted_unit_dtl  AS dtl ON dtl.cm_item_seq = dff.cm_item_seq AND dtl.receptacle_seq = dff.receptacle_seq
inner join micros.emp_def               AS em1 ON em1.emp_seq = dff.employee_seq
inner join micros.emp_def               AS em2 ON em2.emp_seq = dff.count_emp_seq
WHERE   rcp.open_business_date BETWEEN '$fechaInConsulta' AND '$fechaFnConsulta'
ORDER BY rcp.open_business_date, em1.last_name, em1.first_name, Concepto";
//DATOS FORMAS PAGO
	$result = sybase_query($queryFormasPago, $dbh, false) or die('error');
	$i = 0;
	while($row = sybase_fetch_object($result)) { 
		$lc_regs ['Detalle'][$i]= array(
			"Concepto"	=> $row -> Concepto,
			"Contado"	=> round($row -> Contado, 0),
			"Sistema"	=> round($row -> Sistema, 0),
			"Diferencia"	=> round($row -> Diferencia,0),
			"Fecha"		=> $row -> Fecha,
			"Empleado"	=> utf8_encode($row -> Empleado),
			"ContadoPor"	=> utf8_encode($row -> ContadoPor)			
		);
		$i++;
	}
	sybase_free_result ($result);
	$json = json_encode($lc_regs);
	print_r($json);

}

?>
	