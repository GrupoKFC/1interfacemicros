
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
//$dbh=@sybase_connect($server, $user, $pwd) or die('Cannot connect');
$dbh= odbc_connect($server,$user,$pwd) or die('Cannot connect');

///////////////////////// QUERYS //////////////////////
if($lc_CodCadena == 1){
	$queryVtaMedios = " 
SELECT  IF(rvcdef.name = 'KFC' or rvcdef.name = 'WENDY''S' or rvcdef.name = 'CHINAWOK'  or rvcdef.name = 'CHINA WOK'  )
					THEN 'COUNTER'
					ELSE rvcdef.name
				ENDIF                                                           AS RVC,
			SUM 
            (
                IF(dsdef.obj_num IS NOT NULL)  
                THEN -1.0 * dtl.chk_ttl
                ELSE 0.00
                ENDIF
            )                                               AS DONACION, 
SUM (
		    IF(tmdef.obj_num IS NOT NULL AND tmdef.obj_num IN ('4003', '4004', '4005'))  
			THEN dtl.chk_ttl
            		ELSE 0
            	    ENDIF             
        	 )                                            AS GROUPON,


				CONVERT(CHAR(12), tdtl.business_date, 112) 			            AS BUSINESS_DATE,
				SUM (
					IF(dtl.dtl_type IN('D', 'M')) 
						THEN dtl.chk_ttl - dtl.inclusive_tax_ttl
						ELSE 0.00
					ENDIF 
				)         +DONACION   -GROUPON                                                   AS NET_TTL,
				SUM (
					IF(dtl.dtl_type IN('D', 'M')) 
						THEN dtl.chk_ttl
						ELSE 0.00
					ENDIF             
				)            +DONACION -GROUPON                                                  AS GROSS_TTL,
  ISNULL(
					( select TOP 1 IF(tmdef2.obj_num IN ('10015','10014','10012','10011') )
						THEN (
							IF (REPLACE(tmdef2.name, 'Cv ', '') = 'Py')
								THEN 'PedidosYa'
								ELSE REPLACE(tmdef2.name, 'Cv ', '')
							ENDIF
						)
						ELSE IF (rvcdef.name = 'Drive Thru')
							THEN 'Drive Thru'
							ELSE IF (otdef.name IN ('Aqui', 'Servir'))
								THEN 'Local'
								ELSE otdef.name 
							ENDIF
						ENDIF
					ENDIF
					FROM micros.tmed_dtl  tmdtl
         JOIN micros.tmed_def  tmdef2 ON tmdef2.tmed_seq      = tmdtl.tmed_seq
         WHERE tmdtl.trans_seq   = tdtl.trans_seq
		 ),'Drive Thru'
				)              as ORDER_TYPE,
				COUNT(DISTINCT cdtl.chk_seq)                    	            AS TRANS_COUNT
		FROM                  micros.chk_dtl   cdtl 
                 JOIN micros.trans_dtl tdtl    ON tdtl.chk_seq    = cdtl.chk_seq
                 JOIN micros.dtl       dtl     ON dtl.trans_seq   = tdtl.trans_seq
                 JOIN micros.rvc_def   rvcdef  ON rvcdef.rvc_seq  = cdtl.rvc_seq
		JOIN    micros.order_type_def   otdef   ON otdef.order_type_seq = cdtl.order_type_seq
            LEFT JOIN micros.tmed_dtl  tmdtl   ON tmdtl.dtl_seq   = dtl.dtl_seq     AND tmdtl.trans_seq = dtl.trans_seq
            LEFT JOIN micros.tmed_def  tmdef   ON tmdef.tmed_seq  = tmdtl.tmed_seq
            LEFT JOIN micros.dsvc_dtl  dsdtl   ON dsdtl.trans_seq = dtl.trans_seq   AND dsdtl.dtl_seq = dtl.dtl_seq AND (dtl.dtl_status & 0x0080000000) = 0x0080000000
            LEFT JOIN micros.dsvc_def  dsdef   ON dsdef.dsvc_seq  = dsdtl.dsvc_seq  AND dsdef.obj_num IN ('11')

		WHERE   cdtl.chk_open = 'F' 
				AND cdtl.ob_ccs05_chk_cancelled = 'F' 
				AND cdtl.sub_ttl <> 0.00
				AND cdtl.amt_due_ttl = 0.0 
				AND dtl.record_type <> 'S'
				AND dtl.dtl_type <> 'R'  --not reference
				AND chk_ttl <> 0.00
				AND (dtl.ob_dtl05_void_flag ='F' OR dtl.ob_error_correct = 'F')
				AND (dtl.dtl_status & 0x0002000000) = 0x0000000000 --NOT VOIDED        
				AND CONVERT(CHAR(12), tdtl.business_date, 112) ='$fecha'
		GROUP BY    tdtl.business_date, RVC, ORDER_TYPE
		HAVING      GROSS_TTL <> 0.00
";
	} else if($lc_CodCadena == 2){
		$queryVtaMedios ="
SELECT  IF(rvcdef.name = 'KFC' or rvcdef.name = 'WENDY''S' or rvcdef.name = 'CHINAWOK'  or rvcdef.name = 'CHINA WOK'  )					
					THEN 'COUNTER'
					ELSE rvcdef.name
				ENDIF                                                           AS RVC,
			SUM 
            (
                IF(dsdef.obj_num IS NOT NULL)  
                THEN -1.0 * dtl.chk_ttl
                ELSE 0.00
                ENDIF
            )                                               AS DONACION, 

				CONVERT(CHAR(12), tdtl.business_date, 112) 			            AS BUSINESS_DATE,
SUM(
                IF(tmdef.obj_num IS NOT NULL AND tmdef.obj_num IN ('4003','4004', '4005'))  
                THEN dtl.chk_ttl
                ELSE 0
                ENDIF             
            )                                               AS GROUPON,

				SUM (
					IF(dtl.dtl_type IN('D', 'M')) 
						THEN dtl.chk_ttl - dtl.inclusive_tax_ttl
						ELSE 0.00
					ENDIF 
				)      +DONACION   - GROUPON                                                      AS NET_TTL,
				SUM (
					IF(dtl.dtl_type IN('D', 'M')) 
						THEN dtl.chk_ttl
						ELSE 0.00
					ENDIF             
				)            +DONACION    - GROUPON                                               AS GROSS_TTL,
  ISNULL(
					( select TOP 1 IF(tmdef2.obj_num IN ('2010', '2013', '2014', '2015') )
						THEN (
							IF (REPLACE(tmdef2.name, 'Cv ', '') = 'Py')
								THEN 'PedidosYa'
								ELSE REPLACE(tmdef2.name, 'Cv ', '')
							ENDIF
						)
						ELSE IF (rvcdef.name = 'Drive Thru')
							THEN 'Drive Thru'
							ELSE IF (otdef.name IN ('Aqui', 'Servir'))
								THEN 'Local'
								ELSE otdef.name 
							ENDIF
						ENDIF
					ENDIF
					FROM micros.tmed_dtl  tmdtl
         JOIN micros.tmed_def  tmdef2 ON tmdef2.tmed_seq      = tmdtl.tmed_seq
         WHERE tmdtl.trans_seq   = tdtl.trans_seq
		 ),'Drive Thru'
				)              as ORDER_TYPE,
				COUNT(DISTINCT cdtl.chk_seq)                    	            AS TRANS_COUNT
		FROM                  micros.chk_dtl   cdtl 
                 JOIN micros.trans_dtl tdtl    ON tdtl.chk_seq    = cdtl.chk_seq
                 JOIN micros.dtl       dtl     ON dtl.trans_seq   = tdtl.trans_seq
                 JOIN micros.rvc_def   rvcdef  ON rvcdef.rvc_seq  = cdtl.rvc_seq
		JOIN    micros.order_type_def   otdef   ON otdef.order_type_seq = cdtl.order_type_seq
            LEFT JOIN micros.tmed_dtl  tmdtl   ON tmdtl.dtl_seq   = dtl.dtl_seq     AND tmdtl.trans_seq = dtl.trans_seq
            LEFT JOIN micros.tmed_def  tmdef   ON tmdef.tmed_seq  = tmdtl.tmed_seq
            LEFT JOIN micros.dsvc_dtl  dsdtl   ON dsdtl.trans_seq = dtl.trans_seq   AND dsdtl.dtl_seq = dtl.dtl_seq AND (dtl.dtl_status & 0x0080000000) = 0x0080000000
            LEFT JOIN micros.dsvc_def  dsdef   ON dsdef.dsvc_seq  = dsdtl.dsvc_seq  AND dsdef.obj_num IN ('11')

		WHERE   cdtl.chk_open = 'F' 
				AND cdtl.ob_ccs05_chk_cancelled = 'F' 
				AND cdtl.sub_ttl <> 0.00
				AND cdtl.amt_due_ttl = 0.0 
				AND dtl.record_type <> 'S'
				AND dtl.dtl_type <> 'R'  --not reference
				AND chk_ttl <> 0.00
				AND (dtl.ob_dtl05_void_flag ='F' OR dtl.ob_error_correct = 'F')
				AND (dtl.dtl_status & 0x0002000000) = 0x0000000000 --NOT VOIDED        
				AND CONVERT(CHAR(12), tdtl.business_date, 112) ='$fecha'
		GROUP BY    tdtl.business_date, RVC, ORDER_TYPE
		HAVING      GROSS_TTL <> 0.00
		";
} else if($lc_CodCadena == 3){
	$queryVtaMedios = "

SELECT  IF(rvcdef.name = 'KFC' or rvcdef.name = 'WENDY''S' or rvcdef.name = 'CHINAWOK'  or rvcdef.name = 'CHINA WOK'  )
					THEN 'COUNTER'
					ELSE rvcdef.name
				ENDIF                                                           AS RVC,
			SUM 
            (
                IF(dsdef.obj_num IS NOT NULL)  
                THEN -1.0 * dtl.chk_ttl
                ELSE 0.00
                ENDIF
            )                                               AS DONACION, 
SUM 
            (
                IF(tmdef.obj_num IS NOT NULL AND tmdef.obj_num IN ('2006','2007','2008','4003','4004'))  
                THEN dtl.chk_ttl
                ELSE 0
                ENDIF             
            )                                           AS GROUPON,


				CONVERT(CHAR(12), tdtl.business_date, 112) 			            AS BUSINESS_DATE,
				SUM (
					IF(dtl.dtl_type IN('D', 'M')) 
						THEN dtl.chk_ttl - dtl.inclusive_tax_ttl
						ELSE 0.00
					ENDIF 
				)    +DONACION     -GROUPON                                                      AS NET_TTL,
				SUM (
					IF(dtl.dtl_type IN('D', 'M')) 
						THEN dtl.chk_ttl
						ELSE 0.00
					ENDIF             
				)            +DONACION    -GROUPON                                               AS GROSS_TTL,
  ISNULL(
					( select TOP 1 IF(tmdef2.obj_num IN ('2011', '2012', '2013', '2014'))
						THEN (
							IF (REPLACE(tmdef2.name, 'Cv ', '') = 'Py')
								THEN 'PedidosYa'
								ELSE REPLACE(tmdef2.name, 'Cv ', '')
							ENDIF
						)
						ELSE IF (rvcdef.name = 'Drive Thru')
							THEN 'Drive Thru'
							ELSE IF (otdef.name IN ('Aqui', 'Servir'))
								THEN 'Local'
								ELSE otdef.name 
							ENDIF
						ENDIF
					ENDIF
					FROM micros.tmed_dtl  tmdtl
         JOIN micros.tmed_def  tmdef2 ON tmdef2.tmed_seq      = tmdtl.tmed_seq
         WHERE tmdtl.trans_seq   = tdtl.trans_seq
		 ),'Drive Thru'
				)              as ORDER_TYPE,
				COUNT(DISTINCT cdtl.chk_seq)                    	            AS TRANS_COUNT
		FROM                  micros.chk_dtl   cdtl 
                 JOIN micros.trans_dtl tdtl    ON tdtl.chk_seq    = cdtl.chk_seq
                 JOIN micros.dtl       dtl     ON dtl.trans_seq   = tdtl.trans_seq
                 JOIN micros.rvc_def   rvcdef  ON rvcdef.rvc_seq  = cdtl.rvc_seq
		JOIN    micros.order_type_def   otdef   ON otdef.order_type_seq = cdtl.order_type_seq
            LEFT JOIN micros.tmed_dtl  tmdtl   ON tmdtl.dtl_seq   = dtl.dtl_seq     AND tmdtl.trans_seq = dtl.trans_seq
            LEFT JOIN micros.tmed_def  tmdef   ON tmdef.tmed_seq  = tmdtl.tmed_seq
            LEFT JOIN micros.dsvc_dtl  dsdtl   ON dsdtl.trans_seq = dtl.trans_seq   AND dsdtl.dtl_seq = dtl.dtl_seq AND (dtl.dtl_status & 0x0080000000) = 0x0080000000
            LEFT JOIN micros.dsvc_def  dsdef   ON dsdef.dsvc_seq  = dsdtl.dsvc_seq  AND dsdef.obj_num IN ('11')

		WHERE   cdtl.chk_open = 'F' 
				AND cdtl.ob_ccs05_chk_cancelled = 'F' 
				AND cdtl.sub_ttl <> 0.00
				AND cdtl.amt_due_ttl = 0.0 
				AND dtl.record_type <> 'S'
				AND dtl.dtl_type <> 'R'  --not reference
				AND chk_ttl <> 0.00
				AND (dtl.ob_dtl05_void_flag ='F' OR dtl.ob_error_correct = 'F')
				AND (dtl.dtl_status & 0x0002000000) = 0x0000000000 --NOT VOIDED        
				AND CONVERT(CHAR(12), tdtl.business_date, 112) ='$fecha'
		GROUP BY    tdtl.business_date, RVC, ORDER_TYPE
		HAVING      GROSS_TTL <> 0.00

				";
}

//VENTA POR MEDIOS
$result = odbc_exec($dbh,$queryVtaMedios, false) or die('error');
$lc_regs = null;
$i = 0;
while($row = odbc_fetch_object($result)) { 
	$lc_regs ['VtaMedios'][$i]= array(
		"ORDER_TYPE"	=> $row -> ORDER_TYPE,  
		"NET_TTL"		=> $row -> NET_TTL,
		"GROSS_TTL"		=> $row -> GROSS_TTL,
		"TRANS_COUNT"	=> $row -> TRANS_COUNT,
		"CENTER"		=> $row -> RVC,
		"FECHA"			=> $row -> BUSINESS_DATE,
"DONACION"				=> $row -> DONACION, 	
		"RESTAURANTE" 	=> $codRest,
	);
	$i++;
}
$jsVtaMedios  = (json_encode($lc_regs));
odbc_free_result ($result);

print_r($jsVtaMedios);
