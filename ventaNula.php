
<?php
include("conexion/clase_sql.php");
include("clases/clase_interfaz.php");
$lc_inter = new interfaz();
/////////////////////////DATOS DE ENTRADA//////////////////////
$codRest = $_GET['codRest'];
$codUsuario = $_GET['codUsuario'];
$fecha = $_GET['fecha'];
list($dia, $mes, $anio) = explode("/", $fecha);
$fechaConsulta = $anio . $mes . $dia;

/////////////////////////VALIDACIONES//////////////////////
//FECHA

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

	/////////////////////////CONEXI�N A MICROS//////////////////////
	
$server = $lc_Odbc;
$user   = 'dba';
$pwd    = 'micros3700';
$db     = 'micros';
$lc_regs;
$dbh=odbc_connect($server, $user, $pwd) or die('Cannot connect');


	

//////////////////////QUERYS IMPRESORA FISCAL/////////////////////////
	
// ANULACIONES
$queryAnulacion = "
	SELECT subtotal12 as TOTAL_BRUTO, 
		taxttl1 AS IVA, 
		FCRINVNUMBER as BOLETA,
		1 TRANS 
	FROM micros.fcr_invoice_data
	WHERE INVOICESTATUS=0 AND subtotal12 IS NOT NULL AND MICROSBSNZDATE ='$fechaConsulta'";


$result = odbc_exec($dbh,$queryAnulacion,  false) or die('error');
$lc_regs = null;
$i = 0;

while($row = odbc_fetch_object($result)) { 
	$lc_regs ['Anuladas'][$i]= array(
		 "TOTAL_ANULADO"	=> $row -> TOTAL_BRUTO,
		 "IVA"			=> $row -> IVA,
		 "BOLETA"		=> $row -> BOLETA,
		 "TRANSACCIONES"	=> $row -> TRANS 
	);
	$i++;
}
$jsAnulada = (json_encode($lc_regs));
odbc_free_result ($result);

print_r($jsAnulada);
		
		