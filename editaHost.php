<?php 
include("conexion/clase_sql.php");
include("clases/clase_hostMicros.php");
header('Access-Control-Allow-Origin: *');
$lc_micros = new micros();

$ip = $_POST['Ip'];
$Tienda = $_POST['Tienda'];
$codRest = $_POST['codRest'];

$fn = "/etc/freetds.conf"; 

$name = $Tienda."Cl";
$host = "	host = ".$ip."\n";
$port = "	port = 2638\n";
$tdsv = "	tds version = 5.0\n";
$rept = 0;
$i = 0;
$j = 0;
$nameAnt = '';

$file = file($fn); 
foreach($file as $text1) { 
	if (strpos($text1, $name) !== false) {
		$rept++;
		$j = $i;
		$nameAnt = $text1;
	}

	if($i = $j+1 && $rept != 0) {
		if (strpos($text1, $ip) !== false) {
			$rept = 0;
			$j = -1;
			break;
		}
	}
	$i++;
}

if ($rept == 0){
	$name = "[".$Tienda."Cl]\n";
	$nameOdbc = $Tienda."Cl";
} else {
	$name = "[".$Tienda."Cl".$rept."]\n";
	$nameOdbc = $Tienda."Cl".$rept;
}

if ($j+1 !=  0){
	$file = fopen($fn, "a+"); 
	fwrite($file, $name);
	fwrite($file, $host);
	fwrite($file, $port);
	fwrite($file, $tdsv);
	fclose($file); 
	$lc_micros->actualizaHost($nameOdbc, $codRest, $ip);
	echo 'Se ha creado la conexión correctamente';
} else {
	$order   = array("[", "\n", "]");
	$replace = '';
	$nameAnt = str_replace($order, $replace, $nameAnt);
	$lc_micros->actualizaHost($nameAnt, $codRest, $ip);
	echo 'Se ha actualizado la conexión correctamente';}

?>