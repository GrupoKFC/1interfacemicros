
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
    $port=2638;  
    $user = 'dba';
    $pwd = 'micros3700';
    $db = 'micros';
    $server = "DRIVER={Adaptive Server Enterprise};Server=".$lc_IP.";Database=".$db.";Port=".$port;
    $dbh = odbc_connect($server, $user, $pwd) or die('Cannot connect');

    ///////////////////////// QUERYS //////////////////////
    //VENTAS POR PRODUCTO
    if ($lc_CodCadena == 1) {
        $queryXProducto = "
			
	 		SELECT  CONVERT(CHAR(12), tdtl.business_date, 112)  					AS BUSINESS_DATE,
					midef.obj_num                            						AS PLU,
					midef.name_1                             						AS MI_NAME,
					midef.name_2                             						AS MI_PLU2,
					midef.key_num                            						AS MI_PLU3,
					SUM(dtl.chk_cnt)                         						AS MI_COUNT,
					SUM(dtl.chk_ttl)                         						AS MI_TOTAL,
					NOW()                                    						AS TIME_STAMP,
					0.00                                     						AS EXEMPT_TTL,
					SUM(dtl.rpt_ttl)                         						AS BASE_TTL,
					SUM(dtl.chk_ttl - dtl.inclusive_tax_ttl) 						AS NET_TTL,
					SUM(dtl.inclusive_tax_ttl)               						AS TAX_TTL,
					(if MI_COUNT>0.00 then  BASE_TTL / MI_COUNT else 0 endif)      	AS UNIT_BASE_TTL,
					0.00                                     						AS UNIT_ZERO_BASE_TTL,
					(IF MI_COUNT>0.00 THEN  NET_TTL / MI_COUNT  ELSE 0 ENDIF)       AS UNIT_NET_TTL,
					(IF MI_COUNT>0.00 THEN  TAX_TTL / MI_COUNT  ELSE 0 ENDIF)       AS UNIT_TAX,
					(IF MI_COUNT>0.00 THEN  MI_TOTAL / MI_COUNT ELSE 0 ENDIF)       AS UNIT_GROSS_TTL,
					0.00                                     						AS DISCOUNT,
					0.00                                     						AS SERVICES,
					IF(rvcdef.name = 'KFC' or rvcdef.name = 'WENDY''S' or rvcdef.name = 'CHINAWOK'  )
						THEN 'COUNTER'
						ELSE rvcdef.name
					ENDIF                                               			AS RVC,
					ISNULL(
						( select top 1 IF(tmdef2.obj_num IN ('10015','10014','10012','10011'))
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
				         WHERE tmdtl.trans_seq   = tdtl.trans_seq),'Drive Thru'
					)                                                               AS ORDER_TYPE
			FROM    micros.chk_dtl   		cdtl
			JOIN 	micros.rvc_def   		rvcdef  ON rvcdef.rvc_seq  = cdtl.rvc_seq
			JOIN 	micros.order_type_def  	otdef	ON otdef.order_type_seq = cdtl.order_type_seq
			JOIN 	micros.trans_dtl 		tdtl    ON tdtl.chk_seq    = cdtl.chk_seq
			JOIN 	micros.dtl       		dtl     ON dtl.trans_seq   = tdtl.trans_seq
			JOIN 	micros.mi_dtl    		midtl   ON midtl.trans_seq = dtl.trans_seq AND midtl.dtl_seq = dtl.dtl_seq
			JOIN 	micros.mi_def    		midef   ON midef.mi_seq    = midtl.mi_seq
			                       					WHERE   cdtl.chk_open = 'F'
					AND cdtl.ob_ccs05_chk_cancelled = 'F'
					AND rvcdef.obj_num IN ('1', '2', '3')
					AND midef.obj_num NOT IN ('1475', '1476', '1478', '1477', '2022', '121', '122', '123', '126', '2034','125','2035','111343','111344','112046','111346')

					AND convert(char(12), tdtl.business_date, 112) ='$fechaConsulta'
			GROUP BY midef.mi_seq, midef.obj_num, midef.name_1, midef.name_2, midef.key_num, tdtl.business_date, RVC, ORDER_TYPE
			HAVING   MI_TOTAL <> 0.00
			ORDER BY tdtl.business_date, midef.name_1";

        //VENTAS POR HORA
        $queryXHora = "
		SELECT  convert(char(12), tdtl.business_date, 112)  AS BUSINESS_DATE,
        	 HOUR(cdtl.chk_clsd_date_time)                	AS PERIOD_HOUR,
		 SUM(
           	   IF(dsdef.obj_num IS NOT NULL)
		      THEN -1.0 * dtl.chk_ttl
 	             ELSE 0.00
            	   ENDIF
        	 ) AS DONACION,
	        SUM (
            	   IF(dtl.dtl_type IN('D', 'M'))
		      THEN dtl.chk_ttl
	             ELSE 0.00
	          ENDIF
        	 ) + DONACION - GROUPON 				AS TENDER_TOTAL,
        	 COUNT(DISTINCT cdtl.chk_seq)                 	AS TRANS_COUNT,
        	 SUM (
	          IF(tmdef.obj_num IS NOT NULL AND tmdef.obj_num IN ('4003', '4004'))
		      THEN dtl.chk_ttl
 	             ELSE 0
            	   ENDIF
        	 )	                                          AS GROUPON
		FROM              micros.chk_dtl   cdtl
             	JOIN micros.trans_dtl tdtl    ON tdtl.chk_seq    = cdtl.chk_seq
             	JOIN micros.dtl       dtl     ON dtl.trans_seq   = tdtl.trans_seq
             	JOIN micros.rvc_def   rvcdef  ON rvcdef.rvc_seq  = cdtl.rvc_seq
        LEFT 	JOIN micros.tmed_dtl  tmdtl   ON tmdtl.dtl_seq   = dtl.dtl_seq     AND tmdtl.trans_seq = dtl.trans_seq
        LEFT 	JOIN micros.tmed_def  tmdef   ON tmdef.tmed_seq  = tmdtl.tmed_seq
        LEFT 	JOIN micros.dsvc_dtl  dsdtl   ON dsdtl.trans_seq = dtl.trans_seq   AND dsdtl.dtl_seq = dtl.dtl_seq AND (dtl.dtl_status & 0x0080000000) = 0x0080000000
        LEFT 	JOIN micros.dsvc_def  dsdef   ON dsdef.dsvc_seq  = dsdtl.dsvc_seq  AND dsdef.obj_num IN ('11')
	 	WHERE       cdtl.chk_open = 'F'
	        AND cdtl.ob_ccs05_chk_cancelled = 'F'
       	 AND (dtl.dtl_status & 0x0002000000) = 0x0000000000
	        AND rvcdef.obj_num IN ('1', '2', '3')
	        AND convert(char(12), business_date, 112) ='$fechaConsulta'
		GROUP BY tdtl.business_date, PERIOD_HOUR
		HAVING   TENDER_TOTAL <> 0.00
		ORDER BY tdtl.BUSINESS_DATE, PERIOD_HOUR";

//DINTRANS - X CAJERO
        $queryXCajero = "(
		SELECT  convert(char(12), rdtl.open_business_date, 112)     AS BUSINESS_DATE,
	        tmdef.name                  AS TENDER_MEDIA,
       	 SUM(cudtl.base_count_amt)   AS TENDER_TOTAL,
	        empdef.obj_num              AS EMPLOYEE_OBJ_NUM,
       	 empdef.chk_name             AS EMPLOYEE_CHK_NAME,
	        ( SELECT  COUNT(distinct tdtl.chk_seq)
	          FROM         micros.cm_receptacle_dtl   rdtl2
                 JOIN micros.cm_transaction_dtl  cmtdtl ON rdtl2.receptacle_seq = cmtdtl.transaction_receptacle_seq
                 JOIN micros.trans_dtl           tdtl   ON tdtl.trans_seq       = cmtdtl.pos_transaction_id
                 JOIN micros.dtl                 dtl    ON dtl.trans_seq        = tdtl.trans_seq
                 JOIN micros.tmed_dtl            tmdtl  ON tmdtl.trans_seq      = dtl.trans_seq AND tmdtl.dtl_seq = dtl.dtl_seq
                 JOIN micros.tmed_def            tmdef2 ON tmdef2.tmed_seq      = tmdtl.tmed_seq
            	   WHERE       rdtl2.receptacle_seq = rdtl.receptacle_seq
                  AND tmdef2.tmed_seq = tmdef.tmed_seq
                  AND cmtdtl.transaction_type = 15
                  AND cmtdtl.business_Date = rdtl.open_business_date
                  AND dtl.chk_ttl <> 0.00
                  AND rdtl2.receptacle_state = 6
        	 )AS TRANS_COUNT
		FROM         micros.cm_receptacle_dtl                     rdtl
        	 JOIN micros.cm_employee_receptacle_assignment_dtl cmemp    ON cmemp.receptacle_seq      = rdtl.receptacle_seq
	        JOIN micros.emp_def                               empdef   ON empdef.emp_seq            = cmemp.employee_seq
       	 JOIN micros.cm_count_dtl                          cdtl     ON cdtl.receptacle_seq       = rdtl.receptacle_seq
	        JOIN micros.cm_counted_unit_dtl                   cudtl    ON cudtl.count_seq           = cdtl.count_seq
       	 JOIN micros.cm_count_unit_dtl                     ctudtl   ON ctudtl.count_item_seq     = cudtl.count_item_seq AND
                                                                      ctudtl.ref_count_unit_seq = cudtl.ref_count_unit_seq
	        JOIN micros.cm_count_item_dtl                     idtl     ON idtl.count_item_seq       = ctudtl.count_item_seq
       	 JOIN micros.cm_count_calc_item_dtl                calcitm  ON calcitm.count_item_seq    = ctudtl.count_item_seq
	        JOIN micros.cm_item_def                           cmitmdef ON cmitmdef.cm_item_seq      = calcitm.component_item_seq
       	 JOIN micros.tmed_def                              tmdef    ON tmdef.tmed_seq            = cmitmdef.res_item_id AND tmdef.type = 'T'
		WHERE   	tmdef.obj_num IN ('11', '17', '18', '19', '20', '23', '25', '26', '27')
		GROUP BY 	BUSINESS_DATE, TENDER_MEDIA, EMPLOYEE_OBJ_NUM, EMPLOYEE_CHK_NAME, tmdef.tmed_seq, empdef.emp_seq, rdtl.open_business_date, rdtl.receptacle_seq, tmdef.tmed_seq
		HAVING 	TENDER_TOTAL <> 0.00 and  convert(char(12), business_date, 112) ='$fechaConsulta'

		UNION

		SELECT  tdtl.business_date      AS BUSINESS_DATE,
	        'DONACION'              AS TENDER_MEDIA,
       	 SUM(-1.0 * dtl.chk_ttl) AS TENDER_TOTAL,
	        empdef.obj_num          AS EMPLOYEE_OBJ_NUM,
       	 empdef.chk_name         AS EMPLOYEE_CHK_NAME,
	        COUNT()                 AS TRANS_COUNT
		FROM         micros.chk_dtl   cdtl
	        JOIN micros.rvc_def   rvcdef    ON rvcdef.rvc_seq  = cdtl.rvc_seq
       	 JOIN micros.trans_dtl tdtl      ON tdtl.chk_seq    = cdtl.chk_seq
	        JOIN micros.dtl       dtl       ON dtl.trans_seq   = tdtl.trans_seq
	        JOIN micros.dsvc_dtl  dsdtl     ON dsdtl.trans_seq = dtl.trans_seq AND dsdtl.dtl_seq = dtl.dtl_seq
       	 JOIN micros.dsvc_def  dsdef     ON dsdef.dsvc_seq  = dsdtl.dsvc_seq
	        JOIN micros.emp_def   empdef    ON empdef.emp_seq  = cdtl.emp_seq
		WHERE       cdtl.chk_open = 'F'
	        AND cdtl.ob_ccs05_chk_cancelled = 'F'
       	 AND (dtl.dtl_status & 0x0082000000) = 0x0080000000
	        AND rvcdef.obj_num IN ('1', '2', '3')
       	 AND dsdef.obj_num IN ('11')
		 AND convert(char(12), tdtl.business_date, 112) ='$fechaConsulta'
		GROUP BY dsdef.dsvc_seq, dsdef.name, empdef.chk_name, empdef.obj_num, tdtl.business_date

		UNION

		SELECT  convert(char(12), tdtl.business_date, 112) AS BUSINESS_DATE,
	        'DISCOUNT_VALUE'             AS TENDER_MEDIA,
       	 SUM(-1.0 * dtl.chk_ttl)/1.19 AS TENDER_TOTAL,
	        empdef.obj_num          AS EMPLOYEE_OBJ_NUM,
       	 empdef.chk_name         AS EMPLOYEE_CHK_NAME,
	        COUNT()                 AS TRANS_COUNT
		FROM         micros.chk_dtl   cdtl
	        JOIN micros.rvc_def   rvcdef    ON rvcdef.rvc_seq  = cdtl.rvc_seq
       	 JOIN micros.trans_dtl tdtl      ON tdtl.chk_seq    = cdtl.chk_seq
	        JOIN micros.dtl       dtl       ON dtl.trans_seq   = tdtl.trans_seq
       	 JOIN micros.dsvc_dtl  dsdtl     ON dsdtl.trans_seq = dtl.trans_seq AND dsdtl.dtl_seq = dtl.dtl_seq
	        JOIN micros.dsvc_def  dsdef     ON dsdef.dsvc_seq  = dsdtl.dsvc_seq
       	 JOIN micros.emp_def   empdef    ON empdef.emp_seq  = cdtl.emp_seq
		WHERE       cdtl.chk_open = 'F'
	        AND cdtl.ob_ccs05_chk_cancelled = 'F'
       	 AND (dtl.dtl_status & 0x0082000000) = 0x0000000000
	        AND rvcdef.obj_num IN ('1', '2', '3')
		 AND convert(char(12), business_date, 112) ='$fechaConsulta'
		GROUP BY dsdef.dsvc_seq, dsdef.name, empdef.chk_name, empdef.obj_num, tdtl.business_date
	    ) ORDER BY    BUSINESS_DATE, EMPLOYEE_CHK_NAME";

        // CIERRE DE CAJAS -    TOTALES
        $queryCC = "
		SELECT  convert(char(12), tdtl.business_date, 112) AS BUSINESS_DATE,
         	 SUM (
		    IF(dsdef.obj_num IS NOT NULL)
			THEN -1.0 * dtl.chk_ttl
			ELSE 0.00
            	    ENDIF
        	 )                                            AS DONACION,
        	 SUM(dtl.inclusive_tax_ttl)                   AS TAX_TTL,
        	 SUM (
	           IF(dtl.dtl_type IN('D', 'M'))
			THEN dtl.chk_ttl - dtl.inclusive_tax_ttl
            		ELSE 0.00
            	    ENDIF
        	 ) + DONACION - GROUPON                       AS NET_TTL,
        	 SUM (
            	    IF(dtl.dtl_type IN('D', 'M'))
			THEN dtl.chk_ttl
            		ELSE 0.00
            	    ENDIF
        	 ) + DONACION - GROUPON                       AS GROSS_TTL,
        	 COUNT(DISTINCT cdtl.chk_seq)                 AS TRANS_COUNT,
        	 ( SELECT  SUM(base_count_amt)  AS COUNTED_TOTAL
                 FROM         micros.cm_receptacle_dtl   rdtl
                 JOIN micros.cm_count_dtl        cdtl   ON cdtl.receptacle_seq       = rdtl.receptacle_seq
                 JOIN micros.cm_counted_unit_dtl cudtl  ON cudtl.count_seq           = cdtl.count_seq
                 JOIN micros.cm_count_unit_dtl   ctudtl ON ctudtl.count_item_seq     = cudtl.count_item_seq AND
                                                           ctudtl.ref_count_unit_seq = cudtl.ref_count_unit_seq
                 JOIN micros.cm_count_item_dtl   idtl   ON idtl.count_item_seq       = ctudtl.count_item_seq
            	   WHERE   rdtl.open_business_date = BUSINESS_DATE
        	 ) AS COUNTED_TTL,
        	 COUNTED_TTL - GROSS_TTL                      AS DIFF,
        	 SUM (
            	    IF(tmdef.obj_num IS NOT NULL AND tmdef.obj_num IN ('17', '19'))
		    	THEN dtl.chk_ttl
            	    	ELSE 0.00
            	    ENDIF
        	 )                                            AS CC_TTL,
	        SUM (
            	    IF(tmdef.obj_num IS NOT NULL AND tmdef.obj_num IN ('41', '42', '43', '44', '45', '46', '50', '10004', '10007', '10009', '10011', '10012', '10014'))
			THEN dtl.chk_ttl
		       ELSE 0.00
	           ENDIF
	        )                                            AS COUPON_TTL,
         	 ROUND(COUPON_TTL * (19.0/(100.0 + 19.0)), 0) AS COUPON_TAX,
        	 SUM (
            	    IF(tmdef.obj_num IS NOT NULL AND tmdef.obj_num IN ('41', '42', '43', '44', '45', '46', '50', '10004', '10007', '10009', '10011', '10012', '10014'))
			THEN 1
			ELSE 0
  	           ENDIF
        	 )                                            AS COUPON_COUNT,
        	 SUM (
		    IF(tmdef.obj_num IS NOT NULL AND tmdef.obj_num IN ('4003', '4004', '4005'))
			THEN dtl.chk_ttl
            		ELSE 0
            	    ENDIF
        	 )                                            AS GROUPON,
        	 0.00                                         AS EXEMPT_TTL,
        	 0.00                                         AS COMPENSATION_TTL
		FROM              micros.chk_dtl   cdtl
             	JOIN micros.trans_dtl tdtl    ON tdtl.chk_seq    = cdtl.chk_seq
             	JOIN micros.dtl       dtl     ON dtl.trans_seq   = tdtl.trans_seq
             	JOIN micros.rvc_def   rvcdef  ON rvcdef.rvc_seq  = cdtl.rvc_seq
        LEFT 	JOIN micros.tmed_dtl  tmdtl   ON tmdtl.dtl_seq   = dtl.dtl_seq     AND tmdtl.trans_seq = dtl.trans_seq
        LEFT 	JOIN micros.tmed_def  tmdef   ON tmdef.tmed_seq  = tmdtl.tmed_seq
        LEFT 	JOIN micros.dsvc_dtl  dsdtl   ON dsdtl.trans_seq = dtl.trans_seq   AND dsdtl.dtl_seq = dtl.dtl_seq AND (dtl.dtl_status & 0x0080000000) = 0x0080000000
        LEFT 	JOIN micros.dsvc_def  dsdef   ON dsdef.dsvc_seq  = dsdtl.dsvc_seq  AND dsdef.obj_num IN ('11')
		WHERE       cdtl.chk_open = 'F'
        	 AND cdtl.ob_ccs05_chk_cancelled = 'F'
        	 AND (dtl.dtl_status & 0x0002000000) = 0x0000000000
 	        AND rvcdef.obj_num IN ('1', '2', '3')
       	 AND convert(char(12), tdtl.business_date, 112) ='$fechaConsulta'
		GROUP BY tdtl.business_date
		HAVING   GROSS_TTL <> 0.00
		ORDER BY tdtl.business_date";

        //CREDITOS SIN CUPON
        $queryCredSC = "(
		SELECT  convert(char(12), rdtl.open_business_date, 112) AS BUSINESS_DATE,
	        tmdef.name                  AS TENDER_MEDIA,
       	 SUM(base_count_amt)         AS TENDER_TOTAL,
	        empdef.obj_num              AS EMPLOYEE_OBJ_NUM,
       	 empdef.chk_name             AS EMPLOYEE_CHK_NAME,
	        ( SELECT  COUNT(*)
	          FROM              micros.chk_dtl   cdtl
                  JOIN micros.rvc_def   rvcdef    ON rvcdef.rvc_seq  = cdtl.rvc_seq
                  JOIN micros.trans_dtl tdtl      ON tdtl.chk_seq    = cdtl.chk_seq
                  JOIN micros.dtl       dtl       ON dtl.trans_seq   = tdtl.trans_seq
                  JOIN micros.tmed_dtl  tmdtl     ON tmdtl.trans_seq = dtl.trans_seq AND tmdtl.dtl_seq = dtl.dtl_seq
                  JOIN micros.tmed_def  tmdef2    ON tmdef2.tmed_seq = tmdtl.tmed_seq
                  JOIN micros.emp_def   empdef2   ON empdef2.emp_seq = cdtl.emp_seq
	          WHERE           cdtl.chk_open = 'F'
                  AND cdtl.ob_ccs05_chk_cancelled = 'F'
                  AND (dtl.dtl_status & 0x0002000000) = 0x0000000000
                  AND rvcdef.obj_num IN ('1', '2', '3')
                  AND tdtl.business_Date = rdtl.open_business_date
                  AND tmdef2.tmed_seq = tmdef.tmed_seq
                  AND empdef2.emp_seq = empdef.emp_seq
	        )                           AS TRANS_COUNT
		FROM         micros.cm_receptacle_dtl                     rdtl
	        JOIN micros.cm_employee_receptacle_assignment_dtl cmemp    ON cmemp.receptacle_seq   = rdtl.receptacle_seq
       	 JOIN micros.emp_def                               empdef   ON empdef.emp_seq         =  cmemp.employee_seq
	        JOIN micros.cm_count_dtl                          cdtl     ON cdtl.receptacle_seq    = rdtl.receptacle_seq
       	 JOIN micros.cm_counted_unit_dtl     			  cudtl    ON cudtl.count_seq        = cdtl.count_seq
	        JOIN micros.cm_count_unit_dtl       			  ctudtl   ON ctudtl.count_item_seq  = cudtl.count_item_seq AND ctudtl.ref_count_unit_seq = cudtl.ref_count_unit_seq
       	 JOIN micros.cm_count_item_dtl       			  idtl     ON idtl.count_item_seq    = ctudtl.count_item_seq
	        JOIN micros.cm_count_calc_item_dtl  			  calcitm  ON calcitm.count_item_seq = ctudtl.count_item_seq
       	 JOIN micros.cm_item_def             			  cmitmdef ON cmitmdef.cm_item_seq   = calcitm.component_item_seq
	        JOIN micros.tmed_def                			  tmdef    ON tmdef.tmed_seq         = cmitmdef.res_item_id AND tmdef.type = 'T'
		WHERE   tmdef.obj_num IN ('41', '42', '43', '44', '45', '50', '10004', '10007', '10009', '10011', '10012', '10014')
		GROUP BY 	BUSINESS_DATE, TENDER_MEDIA, EMPLOYEE_OBJ_NUM, EMPLOYEE_CHK_NAME, tmdef.tmed_seq, empdef.emp_seq, open_business_date
		HAVING		TENDER_TOTAL <> 0.00 and  convert(char(12), business_date, 112) ='$fechaConsulta'
	    )
	    ORDER BY 	BUSINESS_DATE, EMPLOYEE_CHK_NAME";

        //TRANSACCIONES - X CAJERO
        $queryTransXCajero = "
		SELECT  convert(char(12), tdtl.business_date, 112)     AS BUSINESS_DATE,
	        empdef.obj_num          AS EMPLOYEE_OBJ_NUM,
       	 empdef.chk_name         AS EMPLOYEE_CHK_NAME,
	        COUNT(distinct cdtl.chk_seq)                 AS TRANS_COUNT
		FROM micros.chk_dtl   cdtl
	        JOIN micros.rvc_def   rvcdef    ON rvcdef.rvc_seq  = cdtl.rvc_seq
       	 JOIN micros.trans_dtl tdtl      ON tdtl.chk_seq    = cdtl.chk_seq
	        JOIN micros.emp_def   empdef    ON empdef.emp_seq  = cdtl.emp_seq
		WHERE cdtl.chk_open = 'F'
	        AND cdtl.ob_ccs05_chk_cancelled = 'F'
       	 AND rvcdef.obj_num IN ('1', '2', '3')
		 AND convert(char(12), tdtl.business_date, 112) ='$fechaConsulta'
		GROUP BY empdef.chk_name, empdef.obj_num, tdtl.business_date
		ORDER BY BUSINESS_DATE, EMPLOYEE_OBJ_NUM";

        //TOTALES - X CAJERO
        $queryTotalXCajero = "
		SELECT  convert(char(12), tdtl.business_date, 112)     AS BUSINESS_DATE,
	        empdef.obj_num                               AS EMPLOYEE_OBJ_NUM,
       	 empdef.chk_name                              AS EMPLOYEE_CHK_NAME,
	        SUM (
	           IF(dsdef.obj_num IS NOT NULL)
			THEN -1.0 * dtl.chk_ttl
		       ELSE 0.00
  	           ENDIF
        	 )                                            AS DONACION,
	        SUM (
	           IF(tmdef.obj_num IS NOT NULL AND tmdef.obj_num IN ('4003','4004','4005'))
			THEN dtl.chk_ttl
	              ELSE 0
               ENDIF
        	 )                                            AS GROUPON,
	        SUM (
	            IF(dtl.dtl_type IN('D', 'M'))
			THEN dtl.chk_ttl
	              ELSE 0.00
		     ENDIF
	        ) + DONACION - GROUPON                       AS TENDER_TOTAL,
        	 COUNT(DISTINCT cdtl.chk_seq)                 AS TRANS_COUNT
      		FROM              micros.chk_dtl   cdtl
             	JOIN micros.trans_dtl tdtl    ON tdtl.chk_seq    = cdtl.chk_seq
             	JOIN micros.dtl       dtl     ON dtl.trans_seq   = tdtl.trans_seq
             	JOIN micros.rvc_def   rvcdef  ON rvcdef.rvc_seq  = cdtl.rvc_seq
             	JOIN micros.emp_def   empdef  ON empdef.emp_seq  = cdtl.emp_seq
        LEFT 	JOIN micros.tmed_dtl  tmdtl   ON tmdtl.dtl_seq   = dtl.dtl_seq     AND tmdtl.trans_seq = dtl.trans_seq
        LEFT 	JOIN micros.tmed_def  tmdef   ON tmdef.tmed_seq  = tmdtl.tmed_seq
        LEFT 	JOIN micros.dsvc_dtl  dsdtl   ON dsdtl.trans_seq = dtl.trans_seq   AND dsdtl.dtl_seq = dtl.dtl_seq AND (dtl.dtl_status & 0x0080000000) = 0x0080000000
        LEFT 	JOIN micros.dsvc_def  dsdef   ON dsdef.dsvc_seq  = dsdtl.dsvc_seq  AND dsdef.obj_num IN ('11')
        	WHERE       cdtl.chk_open = 'F'
	        AND cdtl.ob_ccs05_chk_cancelled = 'F'
       	 AND (dtl.dtl_status & 0x0002000000) = 0x0000000000
	        AND rvcdef.obj_num IN ('1', '2', '3')
		 AND convert(char(12), tdtl.business_date, 112) ='$fechaConsulta'
        	GROUP BY tdtl.business_date, empdef.obj_num, empdef.chk_name
		ORDER BY tdtl.business_date, empdef.obj_num, empdef.chk_name";

        //DELIVERY - CAJEROS
        $queryDelivery = "
		SELECT  convert(char(12), tdtl.business_date, 112)     AS BUSINESS_DATE,
        	 empdef.chk_name     AS EMPLOYEE_CHK_NAME,
 	        SUM(dtl.chk_ttl)    AS TENDER_TOTAL,
        	 COUNT(DISTINCT cdtl.chk_seq) AS TRANS_COUNT
		FROM         micros.chk_dtl cdtl
	        JOIN micros.trans_dtl tdtl ON tdtl.chk_seq   = cdtl.chk_seq
       	 JOIN micros.dtl dtl        ON dtl.trans_Seq  = tdtl.trans_seq
	        JOIN micros.emp_def empdef ON empdef.emp_seq = tdtl.trans_emp_seq
		WHERE       cdtl.chk_open = 'F'
	        AND cdtl.ob_ccs05_chk_cancelled = 'F'
       	 AND (dtl.dtl_status & 0x0002000000) = 0x0000000000
	        AND tdtl.chk_emp_seq <> tdtl.trans_emp_seq
       	 AND dtl.dtl_type = 'T' AND dtl.chk_ttl <> 0.00
	        AND tdtl.chk_emp_seq = '163'
		 AND convert(char(12), tdtl.business_date, 112) ='$fechaConsulta'
		GROUP BY tdtl.business_date, empdef.emp_seq, empdef.chk_name
		ORDER BY tdtl.business_date, empdef.chk_name";

        // CUPONES - GRUPON
        $queryCupones = "
		SELECT  tdtl.business_date      AS BUSINESS_DATE,
	        midef.obj_num           AS PLU,
       	 midef.name_1            AS MI_NAME,
		 IF(midef.obj_num = '2022')
		      THEN 'CUPONATIC LATAM SPA'
 	       	ELSE IF(midef.obj_num IN ('121', '122', '123') )
				THEN 'SHOWTIME SPA'
			ELSE 'Groupon'
            	   ENDIF
            	   ENDIF
		 AS TENDER_MEDIA,
	        SUM(dtl.chk_ttl)        AS TENDER_TOTAL,
	        empdef.obj_num          AS EMPLOYEE_OBJ_NUM,
	        empdef.chk_name         AS EMPLOYEE_CHK_NAME,
	        COUNT()                 AS TRANS_COUNT
		FROM         micros.chk_dtl   cdtl
	        JOIN micros.rvc_def   rvcdef    ON rvcdef.rvc_seq  = cdtl.rvc_seq
       	 JOIN micros.trans_dtl tdtl      ON tdtl.chk_seq    = cdtl.chk_seq
	        JOIN micros.dtl       dtl       ON dtl.trans_seq   = tdtl.trans_seq
       	 JOIN micros.mi_dtl    midtl     ON midtl.trans_seq = dtl.trans_seq AND midtl.dtl_seq = dtl.dtl_seq
	        JOIN micros.mi_def    midef     ON midef.mi_seq    = midtl.mi_seq
       	 JOIN micros.emp_def   empdef    ON empdef.emp_seq  = cdtl.emp_seq
		WHERE       cdtl.chk_open = 'F'
	        AND cdtl.ob_ccs05_chk_cancelled = 'F'
	        AND (dtl.dtl_status & 0x0002000000) = 0x0000000000
	        AND rvcdef.obj_num IN ('1', '2', '3')
	        AND midef.obj_num IN ('1475', '1476', '1478', '1477', '2022', '121', '122', '123', '126', '125','2034','2035','111343','111344','112046','111346')
		 AND convert(char(12), business_date, 112) ='$fechaConsulta'
		GROUP BY midef.mi_seq, midef.obj_num, midef.name_1, empdef.chk_name, empdef.obj_num, tdtl.business_date";

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

				AND CONVERT(CHAR(12), tdtl.business_date, 112) ='$fechaConsulta'
		GROUP BY    tdtl.business_date, RVC, ORDER_TYPE
		HAVING      GROSS_TTL <> 0.00

		";
    } else if ($lc_CodCadena == 2) {

        $queryXProducto='select * from micros.chk_dtl';
        $queryXProductos = "
      

			SELECT      CONVERT(CHAR(12), tdtl.business_date, 112)  AS BUSINESS_DATE,
            midef.obj_num                               AS PLU,
            midef.name_1                                AS MI_NAME,
            midef.name_2                                AS MI_PLU2,
            midef.key_num                               AS MI_PLU3,
            SUM(dtl.chk_cnt)                            AS MI_COUNT,
            SUM(dtl.chk_ttl)                            AS MI_TOTAL,
            NOW()                                       AS TIME_STAMP,
            0.00                                        AS EXEMPT_TTL,
            SUM(dtl.rpt_ttl)                            AS BASE_TTL,
            SUM(dtl.chk_ttl - dtl.inclusive_tax_ttl)    AS NET_TTL,
            SUM(dtl.inclusive_tax_ttl)                  AS TAX_TTL,
            BASE_TTL / MI_COUNT                         AS UNIT_BASE_TTL,
            0.00                                        AS UNIT_ZERO_BASE_TTL,
            NET_TTL / MI_COUNT                          AS UNIT_NET_TTL,
            TAX_TTL / MI_COUNT                          AS UNIT_TAX,
            MI_TOTAL / MI_COUNT                         AS UNIT_GROSS_TTL,
            0.00                                        AS DISCOUNT,
            0.00                                        AS SERVICES,
	        IF(rvcdef.name = 'KFC' or rvcdef.name = 'WENDY''S' or rvcdef.name = 'CHINAWOK'  )
                THEN 'COUNTER'
                ELSE rvcdef.name
            ENDIF                                               AS RVC,

ISNULL(
						( select top 1 IF(tmdef2.obj_num IN ('2010', '2013', '2014', '2015'))

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
				         WHERE tmdtl.trans_seq   = tdtl.trans_seq),'Drive Thru'
					)                                                               AS ORDER_TYPE

FROM micros.chk_dtl   cdtl
JOIN micros.rvc_def   rvcdef    ON rvcdef.rvc_seq  = cdtl.rvc_seq
JOIN micros.order_type_def  otdef  ON otdef.order_type_seq = cdtl.order_type_seq
JOIN micros.trans_dtl tdtl      ON tdtl.chk_seq    = cdtl.chk_seq
JOIN micros.dtl       dtl       ON dtl.trans_seq   = tdtl.trans_seq
JOIN micros.mi_dtl    midtl     ON midtl.trans_seq = dtl.trans_seq AND midtl.dtl_seq = dtl.dtl_seq
JOIN micros.mi_def    midef     ON midef.mi_seq    = midtl.mi_seq

WHERE   	cdtl.chk_open = 'F'
            AND cdtl.ob_ccs05_chk_cancelled = 'F'  and void_chk_seq is null
            AND dtl.record_type <> 'R' AND dtl.record_type <> 'S' AND dtl.record_type <> 'I'
            AND rvcdef.obj_num IN ('1', '2', '3')

--            AND midef.obj_num NOT IN ('10533')
 AND midef.obj_num NOT IN ('62001','111073','70016','111208', '111112', '111113', '111114')

            AND convert(char(12), tdtl.business_date, 112) ='$fechaConsulta'  
			and (dtl.chk_ttl<>0
            or (midef.name_1 like '%**%' and  dtl.chk_ttl=0))
GROUP BY    midef.mi_seq, midef.obj_num, midef.name_1, midef.name_2, midef.key_num, tdtl.business_date, RVC, ORDER_TYPE
HAVING      MI_COUNT <> 0.00
ORDER BY    tdtl.business_date, midef.name_1";

        //VENTAS POR HORA
        $queryXHora = "
		SELECT  	CONVERT(CHAR(12), tdtl.business_date, 112)  	AS BUSINESS_DATE,
			HOUR(cdtl.chk_clsd_date_time)                	AS PERIOD_HOUR,
			SUM
			(
				IF(dsdef.obj_num IS NOT NULL)
				THEN -1.0 * dtl.chk_ttl
				ELSE 0.00
            	ENDIF
			) 												AS DONACION,
	        SUM
	        (
            	IF(dtl.dtl_type IN('D', 'M'))
				THEN dtl.chk_ttl
	            ELSE 0.00
				ENDIF
			) + DONACION - GROUPON 							AS TENDER_TOTAL,
			COUNT(DISTINCT cdtl.chk_seq)                 	AS TRANS_COUNT,
			SUM
			(
	          IF(tmdef.obj_num IS NOT NULL AND tmdef.obj_num IN ('4003', '4004', '4005'))
				THEN dtl.chk_ttl
				ELSE 0
				ENDIF
			)	                                          	AS GROUPON
FROM        	      micros.chk_dtl   cdtl
				 JOIN micros.trans_dtl tdtl    ON tdtl.chk_seq    = cdtl.chk_seq
				 JOIN micros.dtl       dtl     ON dtl.trans_seq   = tdtl.trans_seq
JOIN micros.rvc_def   rvcdef  ON rvcdef.rvc_seq  = cdtl.rvc_seq
			LEFT JOIN micros.tmed_dtl  tmdtl   ON tmdtl.dtl_seq   = dtl.dtl_seq     AND tmdtl.trans_seq = dtl.trans_seq
			LEFT JOIN micros.tmed_def  tmdef   ON tmdef.tmed_seq  = tmdtl.tmed_seq
			LEFT JOIN micros.dsvc_dtl  dsdtl   ON dsdtl.trans_seq = dtl.trans_seq   AND dsdtl.dtl_seq = dtl.dtl_seq AND (dtl.dtl_status & 0x0080000000) = 0x0080000000
			LEFT JOIN micros.dsvc_def  dsdef   ON dsdef.dsvc_seq  = dsdtl.dsvc_seq  AND dsdef.obj_num IN ('11')

WHERE           cdtl.chk_open = 'F'
	        AND cdtl.ob_ccs05_chk_cancelled = 'F'
	        AND dtl.record_type <> 'R' AND dtl.record_type <> 'S' AND dtl.record_type <> 'I'
AND rvcdef.obj_num IN ('1', '2', '3')
	        AND CONVERT(CHAR(12), business_date, 112) ='$fechaConsulta'
GROUP BY 	tdtl.business_date, PERIOD_HOUR
HAVING   	TENDER_TOTAL <> 0.00
ORDER BY 	tdtl.BUSINESS_DATE, PERIOD_HOUR";

        //DINTRANS - X CAJERO
        $queryXCajero = "(
    SELECT      CONVERT(CHAR(12), rdtl.open_business_date, 112)     AS BUSINESS_DATE,
                tmdef.name                                          AS TENDER_MEDIA,
                SUM(cudtl.base_count_amt)                           AS TENDER_TOTAL,
                empdef.obj_num                                      AS EMPLOYEE_OBJ_NUM,
                empdef.chk_name                                     AS EMPLOYEE_CHK_NAME,
                (
                    SELECT  COUNT(DISTINCT tdtl.chk_seq)
                    FROM         micros.cm_receptacle_dtl   rdtl2
                            JOIN micros.cm_transaction_dtl  cmtdtl ON rdtl2.receptacle_seq = cmtdtl.transaction_receptacle_seq
                            JOIN micros.trans_dtl           tdtl   ON tdtl.trans_seq       = cmtdtl.pos_transaction_id
                            JOIN micros.dtl                 dtl    ON dtl.trans_seq        = tdtl.trans_seq
                            JOIN micros.tmed_dtl            tmdtl  ON tmdtl.trans_seq      = dtl.trans_seq AND tmdtl.dtl_seq = dtl.dtl_seq
                            JOIN micros.tmed_def            tmdef2 ON tmdef2.tmed_seq      = tmdtl.tmed_seq
                    WHERE       rdtl2.receptacle_seq = rdtl.receptacle_seq
                            AND tmdef2.tmed_seq = tmdef.tmed_seq
                            AND cmtdtl.transaction_type = 15
                            AND cmtdtl.business_Date = rdtl.open_business_date
                            AND dtl.chk_ttl <> 0.00
                            AND rdtl2.receptacle_state = 6
                )                                                   AS TRANS_COUNT
    FROM             micros.cm_receptacle_dtl                     rdtl
                JOIN micros.cm_employee_receptacle_assignment_dtl cmemp    ON cmemp.receptacle_seq      = rdtl.receptacle_seq
                JOIN micros.emp_def                               empdef   ON empdef.emp_seq            = cmemp.employee_seq
                JOIN micros.cm_count_dtl                          cdtl     ON cdtl.receptacle_seq       = rdtl.receptacle_seq
                JOIN micros.cm_counted_unit_dtl                   cudtl    ON cudtl.count_seq           = cdtl.count_seq
                JOIN micros.cm_count_unit_dtl                     ctudtl   ON ctudtl.count_item_seq     = cudtl.count_item_seq AND
                                                                              ctudtl.ref_count_unit_seq = cudtl.ref_count_unit_seq
                JOIN micros.cm_count_item_dtl                     idtl     ON idtl.count_item_seq       = ctudtl.count_item_seq
                JOIN micros.cm_count_calc_item_dtl                calcitm  ON calcitm.count_item_seq    = ctudtl.count_item_seq
                JOIN micros.cm_item_def                           cmitmdef ON cmitmdef.cm_item_seq      = calcitm.component_item_seq
                JOIN micros.tmed_def                              tmdef    ON tmdef.tmed_seq            = cmitmdef.res_item_id AND tmdef.type = 'T'

    WHERE       tmdef.obj_num IN ('11', '17', '18', '19', '20', '23', '25', '26', '27', '28', '30')
    GROUP BY    BUSINESS_DATE, TENDER_MEDIA, EMPLOYEE_OBJ_NUM, EMPLOYEE_CHK_NAME, tmdef.tmed_seq, empdef.emp_seq, rdtl.open_business_date, rdtl.receptacle_seq, tmdef.tmed_seq
    HAVING      TENDER_TOTAL <> 0.00 and  convert(char(12), business_date, 112) ='$fechaConsulta'

UNION

    SELECT      tdtl.business_date                                  AS BUSINESS_DATE,
                'DONACION'                                          AS TENDER_MEDIA,
                SUM(-1.0 * dtl.chk_ttl)                             AS TENDER_TOTAL,
                empdef.obj_num                                      AS EMPLOYEE_OBJ_NUM,
                empdef.chk_name                                     AS EMPLOYEE_CHK_NAME,
                COUNT()                                             AS TRANS_COUNT
    FROM             micros.chk_dtl   cdtl
                JOIN micros.rvc_def   rvcdef    ON rvcdef.rvc_seq  = cdtl.rvc_seq
                JOIN micros.trans_dtl tdtl      ON tdtl.chk_seq    = cdtl.chk_seq
                JOIN micros.dtl       dtl       ON dtl.trans_seq   = tdtl.trans_seq
                JOIN micros.dsvc_dtl  dsdtl     ON dsdtl.trans_seq = dtl.trans_seq AND dsdtl.dtl_seq = dtl.dtl_seq
                JOIN micros.dsvc_def  dsdef     ON dsdef.dsvc_seq  = dsdtl.dsvc_seq
                JOIN micros.emp_def   empdef    ON empdef.emp_seq  = cdtl.emp_seq
    WHERE           cdtl.chk_open = 'F'
                AND cdtl.ob_ccs05_chk_cancelled = 'F'
                AND dtl.record_type <> 'R' AND dtl.record_type <> 'S' AND dtl.record_type <> 'I'
                AND (dtl.dtl_status & 0x0080000000) = 0x0080000000
                AND rvcdef.obj_num IN ('1', '2', '3')
                AND dsdef.obj_num IN ('11')
                AND CONVERT(CHAR(12), tdtl.business_date, 112) ='$fechaConsulta'

    GROUP BY dsdef.dsvc_seq, dsdef.name, empdef.chk_name, empdef.obj_num, tdtl.business_date

UNION

    SELECT      CONVERT(CHAR(12), tdtl.business_date, 112)          AS BUSINESS_DATE,
                'DISCOUNT_VALUE'                                    AS TENDER_MEDIA,
                SUM(-1.0 * dtl.chk_ttl)/1.19                             AS TENDER_TOTAL,
                empdef.obj_num                                      AS EMPLOYEE_OBJ_NUM,
                empdef.chk_name                                     AS EMPLOYEE_CHK_NAME,
                COUNT()                                             AS TRANS_COUNT

    FROM        micros.chk_dtl   cdtl
                JOIN micros.rvc_def   rvcdef    ON rvcdef.rvc_seq  = cdtl.rvc_seq
                JOIN micros.trans_dtl tdtl      ON tdtl.chk_seq    = cdtl.chk_seq
                JOIN micros.dtl       dtl       ON dtl.trans_seq   = tdtl.trans_seq
                JOIN micros.dsvc_dtl  dsdtl     ON dsdtl.trans_seq = dtl.trans_seq AND dsdtl.dtl_seq = dtl.dtl_seq
                JOIN micros.dsvc_def  dsdef     ON dsdef.dsvc_seq  = dsdtl.dsvc_seq
                JOIN micros.emp_def   empdef    ON empdef.emp_seq  = cdtl.emp_seq
    WHERE       cdtl.chk_open = 'F'
                AND cdtl.ob_ccs05_chk_cancelled = 'F'
                AND dtl.record_type <> 'R' AND dtl.record_type <> 'S' AND dtl.record_type <> 'I'
                AND (dtl.dtl_status & 0x0080000000) = 0x0000000000
                AND rvcdef.obj_num IN ('1', '2', '3')
                AND CONVERT(CHAR(12), business_date, 112) = '$fechaConsulta'
    GROUP BY    dsdef.dsvc_seq, dsdef.name, empdef.chk_name, empdef.obj_num, tdtl.business_date

) ORDER BY    BUSINESS_DATE, EMPLOYEE_CHK_NAME";

        // CIERRE DE CAJAS -    TOTALES
        $queryCC = "
		SELECT      CONVERT(CHAR(12), tdtl.business_date, 112)  AS BUSINESS_DATE,
            SUM
            (
                IF(dsdef.obj_num IS NOT NULL)
                THEN -1.0 * dtl.chk_ttl
                ELSE 0.00
                ENDIF
            )                                           AS DONACION,
            SUM(dtl.inclusive_tax_ttl)                  AS TAX_TTL,
            SUM
            (
                IF(dtl.dtl_type IN('D', 'M'))
                THEN dtl.chk_ttl - dtl.inclusive_tax_ttl
                ELSE 0.00
                ENDIF
            ) + DONACION - GROUPON                      AS NET_TTL,
            SUM
            (
                IF(dtl.dtl_type IN('D', 'M'))
                THEN dtl.chk_ttl
                ELSE 0.00
                ENDIF
            ) + DONACION - GROUPON                      AS GROSS_TTL,
            COUNT(DISTINCT cdtl.chk_seq)                AS TRANS_COUNT,
            (
                SELECT  SUM(base_count_amt)  AS COUNTED_TOTAL
                FROM         micros.cm_receptacle_dtl   rdtl
                        JOIN micros.cm_count_dtl        cdtl   ON cdtl.receptacle_seq       = rdtl.receptacle_seq
                        JOIN micros.cm_counted_unit_dtl cudtl  ON cudtl.count_seq           = cdtl.count_seq
                        JOIN micros.cm_count_unit_dtl   ctudtl ON ctudtl.count_item_seq     = cudtl.count_item_seq AND
                                                                  ctudtl.ref_count_unit_seq = cudtl.ref_count_unit_seq
                        JOIN micros.cm_count_item_dtl   idtl   ON idtl.count_item_seq       = ctudtl.count_item_seq
                WHERE   rdtl.open_business_date = BUSINESS_DATE
            )                                           AS COUNTED_TTL,
            COUNTED_TTL - GROSS_TTL                     AS DIFF,
            SUM
            (
                IF(tmdef.obj_num IS NOT NULL AND tmdef.obj_num IN ('17', '19'))
                THEN dtl.chk_ttl
                ELSE 0.00
                ENDIF
            )                                           AS CC_TTL,
            SUM
            (
                IF(tmdef.obj_num IS NOT NULL AND tmdef.obj_num IN ('41', '42', '43', '44', '45', '46', '50', '10004', '10007', '10009', '10011', '10012', '10014', '2004', '2005', '2006', '2007', '2010', '2011', '2012', '2013', '2014'))
                THEN dtl.chk_ttl
                ELSE 0.00
                ENDIF
            )                                           AS COUPON_TTL,
            ROUND(COUPON_TTL*(19.0/(100.0 + 19.0)),0)   AS COUPON_TAX,
            SUM
            (
                IF(tmdef.obj_num IS NOT NULL AND tmdef.obj_num IN ('41', '42', '43', '44', '45', '46', '50', '10004', '10007', '10009', '10011', '10012', '10014', '2004', '2005', '2006', '2007', '2010', '2011', '2012', '2013', '2014'))
                THEN 1
                ELSE 0
                ENDIF
            )                                           AS COUPON_COUNT,
            SUM
            (
                IF(tmdef.obj_num IS NOT NULL AND tmdef.obj_num IN ('4003', '4004', '4005'))
                THEN dtl.chk_ttl
                ELSE 0
                ENDIF
            )                                           AS GROUPON,
            0.00                                        AS EXEMPT_TTL,
            0.00                                        AS COMPENSATION_TTL
FROM                  micros.chk_dtl   cdtl
                 JOIN micros.trans_dtl tdtl    ON tdtl.chk_seq    = cdtl.chk_seq
                 JOIN micros.dtl       dtl     ON dtl.trans_seq   = tdtl.trans_seq
                 JOIN micros.rvc_def   rvcdef  ON rvcdef.rvc_seq  = cdtl.rvc_seq
            LEFT JOIN micros.tmed_dtl  tmdtl   ON tmdtl.dtl_seq   = dtl.dtl_seq     AND tmdtl.trans_seq = dtl.trans_seq
            LEFT JOIN micros.tmed_def  tmdef   ON tmdef.tmed_seq  = tmdtl.tmed_seq
            LEFT JOIN micros.dsvc_dtl  dsdtl   ON dsdtl.trans_seq = dtl.trans_seq   AND dsdtl.dtl_seq = dtl.dtl_seq AND (dtl.dtl_status & 0x0080000000) = 0x0080000000
            LEFT JOIN micros.dsvc_def  dsdef   ON dsdef.dsvc_seq  = dsdtl.dsvc_seq  AND dsdef.obj_num IN ('11')
WHERE       cdtl.chk_open = 'F'
            AND cdtl.ob_ccs05_chk_cancelled = 'F'
            AND dtl.record_type <> 'R' AND dtl.record_type <> 'S' AND dtl.record_type <> 'I'
            AND rvcdef.obj_num IN ('1', '2', '3')
            AND convert(char(12), tdtl.business_date, 112) ='$fechaConsulta'

GROUP BY    tdtl.business_date
HAVING      GROSS_TTL <> 0.00
ORDER BY    tdtl.business_date";

        //CREDITOS SIN CUPON
        $queryCredSC = "(
		SELECT      CONVERT(CHAR(12), rdtl.open_business_date, 112) AS BUSINESS_DATE,
            tmdef.name                                      AS TENDER_MEDIA,
            SUM(base_count_amt)                             AS TENDER_TOTAL,
            empdef.obj_num                                  AS EMPLOYEE_OBJ_NUM,
            empdef.chk_name                                 AS EMPLOYEE_CHK_NAME,
            (
                SELECT  COUNT(*)
                FROM         micros.chk_dtl   cdtl
                        JOIN micros.rvc_def   rvcdef    ON rvcdef.rvc_seq  = cdtl.rvc_seq
                        JOIN micros.trans_dtl tdtl      ON tdtl.chk_seq    = cdtl.chk_seq
                        JOIN micros.dtl       dtl       ON dtl.trans_seq   = tdtl.trans_seq
                        JOIN micros.tmed_dtl  tmdtl     ON tmdtl.trans_seq = dtl.trans_seq AND tmdtl.dtl_seq = dtl.dtl_seq
                        JOIN micros.tmed_def  tmdef2    ON tmdef2.tmed_seq = tmdtl.tmed_seq
                        JOIN micros.emp_def   empdef2   ON empdef2.emp_seq = cdtl.emp_seq
                WHERE       cdtl.chk_open = 'F'
                        AND cdtl.ob_ccs05_chk_cancelled = 'F'
                        AND dtl.record_type <> 'R' AND dtl.record_type <> 'S' AND dtl.record_type <> 'I'
                        AND rvcdef.obj_num IN ('1', '2', '3')
                        AND tdtl.business_Date = rdtl.open_business_date
                        AND tmdef2.tmed_seq = tmdef.tmed_seq
                        AND empdef2.emp_seq = empdef.emp_seq
            )                                               AS TRANS_COUNT
FROM             micros.cm_receptacle_dtl                     rdtl
            JOIN micros.cm_employee_receptacle_assignment_dtl cmemp    ON cmemp.receptacle_seq   = rdtl.receptacle_seq
            JOIN micros.emp_def                               empdef   ON empdef.emp_seq         =  cmemp.employee_seq
            JOIN micros.cm_count_dtl                          cdtl     ON cdtl.receptacle_seq    = rdtl.receptacle_seq
            JOIN micros.cm_counted_unit_dtl                   cudtl    ON cudtl.count_seq        = cdtl.count_seq
            JOIN micros.cm_count_unit_dtl                     ctudtl   ON ctudtl.count_item_seq  = cudtl.count_item_seq AND ctudtl.ref_count_unit_seq = cudtl.ref_count_unit_seq
            JOIN micros.cm_count_item_dtl                     idtl     ON idtl.count_item_seq    = ctudtl.count_item_seq
            JOIN micros.cm_count_calc_item_dtl                calcitm  ON calcitm.count_item_seq = ctudtl.count_item_seq
            JOIN micros.cm_item_def                           cmitmdef ON cmitmdef.cm_item_seq   = calcitm.component_item_seq
            JOIN micros.tmed_def                              tmdef    ON tmdef.tmed_seq         = cmitmdef.res_item_id AND tmdef.type = 'T'
		WHERE   tmdef.obj_num IN ('41', '42', '43', '44', '45', '50', '10004', '10007', '10009', '10011', '10012',
					  '10013','10014', '2004', '2005', '2006', '2007', '2010', '2011', '2012', '2013',
					  '2014', '46','29','31')
GROUP BY    BUSINESS_DATE, TENDER_MEDIA, EMPLOYEE_OBJ_NUM, EMPLOYEE_CHK_NAME, tmdef.tmed_seq, empdef.emp_seq, open_business_date
HAVING      TENDER_TOTAL <> 0.00 AND CONVERT(CHAR(12), business_date, 112) = '$fechaConsulta'
)
ORDER BY    BUSINESS_DATE, EMPLOYEE_CHK_NAME";

        //TRANSACCIONES - X CAJERO
        $queryTransXCajero = "
		SELECT      convert(char(12), tdtl.business_date, 112)      AS BUSINESS_DATE,
--            empdef.obj_num                                  AS EMPLOYEE_OBJ_NUM,
            empdef.chk_name                                 AS EMPLOYEE_CHK_NAME,
            COUNT(distinct cdtl.chk_seq)                    AS TRANS_COUNT

FROM             micros.chk_dtl   cdtl
            JOIN micros.rvc_def   rvcdef    ON rvcdef.rvc_seq  = cdtl.rvc_seq
            JOIN micros.trans_dtl tdtl      ON tdtl.chk_seq    = cdtl.chk_seq
            JOIN micros.emp_def   empdef    ON empdef.emp_seq  = cdtl.emp_seq

WHERE           cdtl.chk_open = 'F'
            AND cdtl.ob_ccs05_chk_cancelled = 'F'
            AND rvcdef.obj_num IN ('1', '2', '3')
            AND CONVERT(CHAR(12), tdtl.business_date, 112) ='$fechaConsulta'

GROUP BY    empdef.chk_name, /*empdef.obj_num,*/ tdtl.business_date
ORDER BY    BUSINESS_DATE--, EMPLOYEE_OBJ_NUM";

        //TOTALES - X CAJERO
        $queryTotalXCajero = "
		SELECT      CONVERT(CHAR(12), tdtl.business_date, 112)      AS BUSINESS_DATE,
            --empdef.obj_num                                  AS EMPLOYEE_OBJ_NUM,
            empdef.chk_name                                 AS EMPLOYEE_CHK_NAME,
            SUM
            (
                IF(dsdef.obj_num IS NOT NULL)
                THEN -1.0 * dtl.chk_ttl
                ELSE 0.00
                ENDIF
            )                                               AS DONACION,
            SUM
            (
                IF(tmdef.obj_num IS NOT NULL AND tmdef.obj_num IN ('4003','4004', '4005'))
                THEN dtl.chk_ttl
                ELSE 0
                ENDIF
            )                                               AS GROUPON,
            SUM
            (
                IF(dtl.dtl_type IN('D', 'M'))
                THEN dtl.chk_ttl
                ELSE 0.00
                ENDIF
            ) + DONACION - GROUPON                          AS TENDER_TOTAL,
            COUNT(DISTINCT cdtl.chk_seq)                    AS TRANS_COUNT

FROM                  micros.chk_dtl   cdtl
                 JOIN micros.trans_dtl tdtl    ON tdtl.chk_seq    = cdtl.chk_seq
                 JOIN micros.dtl       dtl     ON dtl.trans_seq   = tdtl.trans_seq
                 JOIN micros.rvc_def   rvcdef  ON rvcdef.rvc_seq  = cdtl.rvc_seq
                 JOIN micros.emp_def   empdef  ON empdef.emp_seq  = cdtl.emp_seq
            LEFT JOIN micros.tmed_dtl  tmdtl   ON tmdtl.dtl_seq   = dtl.dtl_seq     AND tmdtl.trans_seq = dtl.trans_seq
            LEFT JOIN micros.tmed_def  tmdef   ON tmdef.tmed_seq  = tmdtl.tmed_seq
            LEFT JOIN micros.dsvc_dtl  dsdtl   ON dsdtl.trans_seq = dtl.trans_seq   AND dsdtl.dtl_seq = dtl.dtl_seq AND (dtl.dtl_status & 0x0080000000) = 0x0080000000
            LEFT JOIN micros.dsvc_def  dsdef   ON dsdef.dsvc_seq  = dsdtl.dsvc_seq  AND dsdef.obj_num IN ('11')

WHERE       cdtl.chk_open = 'F'
            AND cdtl.ob_ccs05_chk_cancelled = 'F'
            AND dtl.record_type <> 'R' AND dtl.record_type <> 'S' AND dtl.record_type <> 'I'
            AND rvcdef.obj_num IN ('1', '2', '3')
            AND CONVERT(CHAR(12), tdtl.business_date, 112) = '$fechaConsulta'

GROUP BY    tdtl.business_date/*, empdef.obj_num*/, empdef.chk_name
		ORDER BY tdtl.business_date/*, empdef.obj_num*/, empdef.chk_name";

        //DELIVERY - CAJEROS
        $queryDelivery = "
		SELECT      CONVERT(CHAR(12), tdtl.business_date, 112)      AS BUSINESS_DATE,
            empdef.chk_name                                 AS EMPLOYEE_CHK_NAME,
            SUM(dtl.chk_ttl)                                AS TENDER_TOTAL,
            COUNT(DISTINCT cdtl.chk_seq)                    AS TRANS_COUNT

FROM             micros.chk_dtl cdtl
            JOIN micros.trans_dtl tdtl ON tdtl.chk_seq   = cdtl.chk_seq
            JOIN micros.dtl dtl        ON dtl.trans_Seq  = tdtl.trans_seq
            JOIN micros.emp_def empdef ON empdef.emp_seq = tdtl.trans_emp_seq

WHERE           cdtl.chk_open = 'F'
            AND cdtl.ob_ccs05_chk_cancelled = 'F'
            AND dtl.record_type <> 'R' AND dtl.record_type <> 'S' AND dtl.record_type <> 'I'
            AND tdtl.chk_emp_seq <> tdtl.trans_emp_seq
            AND dtl.dtl_type = 'T' AND dtl.chk_ttl <> 0.00
            AND tdtl.chk_emp_seq = '163'
            AND CONVERT(CHAR(12), tdtl.business_date, 112) = '$fechaConsulta'

GROUP BY    tdtl.business_date, empdef.emp_seq, empdef.chk_name
ORDER BY    tdtl.business_date, empdef.chk_name";

        // CUPONES - GRUPON
        $queryCupones = "
		SELECT  	tdtl.business_date      	AS BUSINESS_DATE,
	        midef.obj_num           	AS PLU,
       	 	midef.name_1            	AS MI_NAME,
		 	IF(midef.obj_num = '111112' OR midef.obj_num =  '111113' OR midef.obj_num =  '111114')
				THEN 'SHOWTIME SPA'
 	        ELSE 'Groupon'
            ENDIF 						AS TENDER_MEDIA,
	        SUM(dtl.chk_ttl)        	AS TENDER_TOTAL,
	        empdef.obj_num          	AS EMPLOYEE_OBJ_NUM,
	        empdef.chk_name         	AS EMPLOYEE_CHK_NAME,
	        COUNT()                 	AS TRANS_COUNT

FROM		     micros.chk_dtl   cdtl
	        JOIN micros.rvc_def   rvcdef    ON rvcdef.rvc_seq  = cdtl.rvc_seq
       	 	JOIN micros.trans_dtl tdtl      ON tdtl.chk_seq    = cdtl.chk_seq
	        JOIN micros.dtl       dtl       ON dtl.trans_seq   = tdtl.trans_seq
       	 	JOIN micros.mi_dtl    midtl     ON midtl.trans_seq = dtl.trans_seq AND midtl.dtl_seq = dtl.dtl_seq
	        JOIN micros.mi_def    midef     ON midef.mi_seq    = midtl.mi_seq
       	 	JOIN micros.emp_def   empdef    ON empdef.emp_seq  = cdtl.emp_seq

WHERE           cdtl.chk_open = 'F'
	        AND cdtl.ob_ccs05_chk_cancelled = 'F'
            AND dtl.record_type <> 'R' AND dtl.record_type <> 'S' AND dtl.record_type <> 'I'
	        AND rvcdef.obj_num IN ('1', '2', '3')
	        AND midef.obj_num IN ('62001','111073','70016','111208', '111112', '111113', '111114')
		 	AND CONVERT(CHAR(12), business_date, 112) ='$fechaConsulta'
GROUP BY midef.mi_seq, midef.obj_num, midef.name_1, empdef.chk_name, empdef.obj_num, tdtl.business_date";

        //VENTA POR MEDIOS
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
				AND CONVERT(CHAR(12), tdtl.business_date, 112) ='$fechaConsulta'
 AND dtl.record_type <> 'I'
            AND rvcdef.obj_num IN ('1', '2', '3')  and void_chk_seq is null
		GROUP BY    tdtl.business_date, RVC, ORDER_TYPE
		HAVING      GROSS_TTL <> 0.00";
    } else if ($lc_CodCadena == 3) {

        $queryXProducto = "
			SELECT      CONVERT(CHAR(12), tdtl.business_date, 112)  AS BUSINESS_DATE,
            midef.obj_num                               AS PLU,
            midef.name_1                                AS MI_NAME,
            midef.name_2                                AS MI_PLU2,
            midef.key_num                               AS MI_PLU3,
            SUM(dtl.chk_cnt)                            AS MI_COUNT,
            SUM(dtl.chk_ttl)                            AS MI_TOTAL,
            NOW()                                       AS TIME_STAMP,
            0.00                                        AS EXEMPT_TTL,
            SUM(dtl.rpt_ttl)                            AS BASE_TTL,
            SUM(dtl.chk_ttl - dtl.inclusive_tax_ttl)    AS NET_TTL,
            SUM(dtl.inclusive_tax_ttl)                  AS TAX_TTL,
            BASE_TTL / MI_COUNT                         AS UNIT_BASE_TTL,
            0.00                                        AS UNIT_ZERO_BASE_TTL,
            NET_TTL / MI_COUNT                          AS UNIT_NET_TTL,
            TAX_TTL / MI_COUNT                          AS UNIT_TAX,
            MI_TOTAL / MI_COUNT                         AS UNIT_GROSS_TTL,
            0.00                                        AS DISCOUNT,
            0.00                                        AS SERVICES,
	        IF(rvcdef.name = 'KFC' or rvcdef.name = 'WENDY''S' or rvcdef.name = 'CHINAWOK'  )
                THEN 'COUNTER'
                ELSE rvcdef.name
            ENDIF                                               AS RVC,

				ISNULL(
						( select top 1 IF(tmdef2.obj_num IN ('2011', '2012', '2013', '2014'))
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
				         WHERE tmdtl.trans_seq   = tdtl.trans_seq),'Drive Thru'
					)                                                               AS ORDER_TYPE

FROM micros.chk_dtl   cdtl
JOIN micros.rvc_def   rvcdef    ON rvcdef.rvc_seq  = cdtl.rvc_seq
JOIN micros.order_type_def  otdef  ON otdef.order_type_seq = cdtl.order_type_seq
JOIN micros.trans_dtl tdtl      ON tdtl.chk_seq    = cdtl.chk_seq
JOIN micros.dtl       dtl       ON dtl.trans_seq   = tdtl.trans_seq
JOIN micros.mi_dtl    midtl     ON midtl.trans_seq = dtl.trans_seq AND midtl.dtl_seq = dtl.dtl_seq
JOIN micros.mi_def    midef     ON midef.mi_seq    = midtl.mi_seq
WHERE   	cdtl.chk_open = 'F'
            AND cdtl.ob_ccs05_chk_cancelled = 'F'  and void_chk_seq is null
            AND dtl.record_type <> 'R' AND dtl.record_type <> 'S' AND dtl.record_type <> 'I'
            AND rvcdef.obj_num IN ('1', '2', '3')
            AND midef.obj_num NOT IN ('10533','10032')
            AND convert(char(12), tdtl.business_date, 112) ='$fechaConsulta'  and (dtl.chk_ttl<>0
            or (midef.name_1 like '%**%' and  dtl.chk_ttl=0))
GROUP BY    midef.mi_seq, midef.obj_num, midef.name_1, midef.name_2, midef.key_num, tdtl.business_date, RVC, ORDER_TYPE
HAVING      MI_COUNT <> 0.00
ORDER BY    tdtl.business_date, midef.name_1";

        //VENTAS POR HORA
        $queryXHora = "
		SELECT  	CONVERT(CHAR(12), tdtl.business_date, 112)  	AS BUSINESS_DATE,
			HOUR(cdtl.chk_clsd_date_time)                	AS PERIOD_HOUR,
			SUM
			(
				IF(dsdef.obj_num IS NOT NULL)
				THEN -1.0 * dtl.chk_ttl
				ELSE 0.00
            	ENDIF
			) 												AS DONACION,
	        SUM
	        (
            	IF(dtl.dtl_type IN('D', 'M'))
				THEN dtl.chk_ttl
	            ELSE 0.00
				ENDIF
			) + DONACION - GROUPON 							AS TENDER_TOTAL,
			COUNT(DISTINCT cdtl.chk_seq)                 	AS TRANS_COUNT,
			SUM
			(
	          IF(tmdef.obj_num IS NOT NULL AND tmdef.obj_num IN ('2006','2007','2008','4003','4004'))
				THEN dtl.chk_ttl
				ELSE 0
				ENDIF
			)	                                          	AS GROUPON
FROM        	      micros.chk_dtl   cdtl
				 JOIN micros.trans_dtl tdtl    ON tdtl.chk_seq    = cdtl.chk_seq
				 JOIN micros.dtl       dtl     ON dtl.trans_seq   = tdtl.trans_seq
JOIN micros.rvc_def   rvcdef  ON rvcdef.rvc_seq  = cdtl.rvc_seq
			LEFT JOIN micros.tmed_dtl  tmdtl   ON tmdtl.dtl_seq   = dtl.dtl_seq     AND tmdtl.trans_seq = dtl.trans_seq
			LEFT JOIN micros.tmed_def  tmdef   ON tmdef.tmed_seq  = tmdtl.tmed_seq
			LEFT JOIN micros.dsvc_dtl  dsdtl   ON dsdtl.trans_seq = dtl.trans_seq   AND dsdtl.dtl_seq = dtl.dtl_seq AND (dtl.dtl_status & 0x0080000000) = 0x0080000000
			LEFT JOIN micros.dsvc_def  dsdef   ON dsdef.dsvc_seq  = dsdtl.dsvc_seq  AND dsdef.obj_num IN ('11')

WHERE           cdtl.chk_open = 'F'
	        AND cdtl.ob_ccs05_chk_cancelled = 'F'
	        AND dtl.record_type <> 'R' AND dtl.record_type <> 'S' AND dtl.record_type <> 'I'
AND rvcdef.obj_num IN ('1', '2', '3')
	        AND CONVERT(CHAR(12), business_date, 112) ='$fechaConsulta'
GROUP BY 	tdtl.business_date, PERIOD_HOUR
HAVING   	TENDER_TOTAL <> 0.00
ORDER BY 	tdtl.BUSINESS_DATE, PERIOD_HOUR";

        //DINTRANS - X CAJERO
        $queryXCajero = "(
    SELECT      CONVERT(CHAR(12), rdtl.open_business_date, 112)     AS BUSINESS_DATE,
                tmdef.name                                          AS TENDER_MEDIA,
                SUM(cudtl.base_count_amt)                           AS TENDER_TOTAL,
                empdef.obj_num                                      AS EMPLOYEE_OBJ_NUM,
                empdef.chk_name                                     AS EMPLOYEE_CHK_NAME,
                (
                    SELECT  COUNT(DISTINCT tdtl.chk_seq)
                    FROM         micros.cm_receptacle_dtl   rdtl2
                            JOIN micros.cm_transaction_dtl  cmtdtl ON rdtl2.receptacle_seq = cmtdtl.transaction_receptacle_seq
                            JOIN micros.trans_dtl           tdtl   ON tdtl.trans_seq       = cmtdtl.pos_transaction_id
                            JOIN micros.dtl                 dtl    ON dtl.trans_seq        = tdtl.trans_seq
                            JOIN micros.tmed_dtl            tmdtl  ON tmdtl.trans_seq      = dtl.trans_seq AND tmdtl.dtl_seq = dtl.dtl_seq
                            JOIN micros.tmed_def            tmdef2 ON tmdef2.tmed_seq      = tmdtl.tmed_seq
                    WHERE       rdtl2.receptacle_seq = rdtl.receptacle_seq
                            AND tmdef2.tmed_seq = tmdef.tmed_seq
                            AND cmtdtl.transaction_type = 15
                            AND cmtdtl.business_Date = rdtl.open_business_date
                            AND dtl.chk_ttl <> 0.00
                            AND rdtl2.receptacle_state = 6
                )                                                   AS TRANS_COUNT
    FROM             micros.cm_receptacle_dtl                     rdtl
                JOIN micros.cm_employee_receptacle_assignment_dtl cmemp    ON cmemp.receptacle_seq      = rdtl.receptacle_seq
                JOIN micros.emp_def                               empdef   ON empdef.emp_seq            = cmemp.employee_seq
                JOIN micros.cm_count_dtl                          cdtl     ON cdtl.receptacle_seq       = rdtl.receptacle_seq
                JOIN micros.cm_counted_unit_dtl                   cudtl    ON cudtl.count_seq           = cdtl.count_seq
                JOIN micros.cm_count_unit_dtl                     ctudtl   ON ctudtl.count_item_seq     = cudtl.count_item_seq AND
                                                                              ctudtl.ref_count_unit_seq = cudtl.ref_count_unit_seq
                JOIN micros.cm_count_item_dtl                     idtl     ON idtl.count_item_seq       = ctudtl.count_item_seq
                JOIN micros.cm_count_calc_item_dtl                calcitm  ON calcitm.count_item_seq    = ctudtl.count_item_seq
                JOIN micros.cm_item_def                           cmitmdef ON cmitmdef.cm_item_seq      = calcitm.component_item_seq
                JOIN micros.tmed_def                              tmdef    ON tmdef.tmed_seq            = cmitmdef.res_item_id AND tmdef.type = 'T'

    WHERE       tmdef.obj_num IN ('11', '17', '19', '20', '23', '25', '26', '27')
    GROUP BY    BUSINESS_DATE, TENDER_MEDIA, EMPLOYEE_OBJ_NUM, EMPLOYEE_CHK_NAME, tmdef.tmed_seq, empdef.emp_seq, rdtl.open_business_date, rdtl.receptacle_seq, tmdef.tmed_seq
    HAVING      TENDER_TOTAL <> 0.00 and  convert(char(12), business_date, 112) ='$fechaConsulta'

UNION

    SELECT      tdtl.business_date                                  AS BUSINESS_DATE,
                'DONACION'                                          AS TENDER_MEDIA,
                SUM(-1.0 * dtl.chk_ttl)                             AS TENDER_TOTAL,
                empdef.obj_num                                      AS EMPLOYEE_OBJ_NUM,
                empdef.chk_name                                     AS EMPLOYEE_CHK_NAME,
                COUNT()                                             AS TRANS_COUNT
    FROM             micros.chk_dtl   cdtl
                JOIN micros.rvc_def   rvcdef    ON rvcdef.rvc_seq  = cdtl.rvc_seq
                JOIN micros.trans_dtl tdtl      ON tdtl.chk_seq    = cdtl.chk_seq
                JOIN micros.dtl       dtl       ON dtl.trans_seq   = tdtl.trans_seq
                JOIN micros.dsvc_dtl  dsdtl     ON dsdtl.trans_seq = dtl.trans_seq AND dsdtl.dtl_seq = dtl.dtl_seq
                JOIN micros.dsvc_def  dsdef     ON dsdef.dsvc_seq  = dsdtl.dsvc_seq
                JOIN micros.emp_def   empdef    ON empdef.emp_seq  = cdtl.emp_seq
    WHERE           cdtl.chk_open = 'F'
                AND cdtl.ob_ccs05_chk_cancelled = 'F'
                AND dtl.record_type <> 'R' AND dtl.record_type <> 'S' AND dtl.record_type <> 'I'
                AND (dtl.dtl_status & 0x0080000000) = 0x0080000000
                AND rvcdef.obj_num IN ('1', '2', '3')
                AND dsdef.obj_num IN ('11')
                AND CONVERT(CHAR(12), tdtl.business_date, 112) ='$fechaConsulta'

    GROUP BY dsdef.dsvc_seq, dsdef.name, empdef.chk_name, empdef.obj_num, tdtl.business_date

UNION

    SELECT      CONVERT(CHAR(12), tdtl.business_date, 112)          AS BUSINESS_DATE,
                'DISCOUNT_VALUE'                                    AS TENDER_MEDIA,
                SUM(-1.0 * dtl.chk_ttl)/1.19                        AS TENDER_TOTAL,
                empdef.obj_num                                      AS EMPLOYEE_OBJ_NUM,
                empdef.chk_name                                     AS EMPLOYEE_CHK_NAME,
                COUNT()                                             AS TRANS_COUNT

    FROM        micros.chk_dtl   cdtl
                JOIN micros.rvc_def   rvcdef    ON rvcdef.rvc_seq  = cdtl.rvc_seq
                JOIN micros.trans_dtl tdtl      ON tdtl.chk_seq    = cdtl.chk_seq
                JOIN micros.dtl       dtl       ON dtl.trans_seq   = tdtl.trans_seq
                JOIN micros.dsvc_dtl  dsdtl     ON dsdtl.trans_seq = dtl.trans_seq AND dsdtl.dtl_seq = dtl.dtl_seq
                JOIN micros.dsvc_def  dsdef     ON dsdef.dsvc_seq  = dsdtl.dsvc_seq
                JOIN micros.emp_def   empdef    ON empdef.emp_seq  = cdtl.emp_seq
    WHERE       cdtl.chk_open = 'F'
                AND cdtl.ob_ccs05_chk_cancelled = 'F'
                AND dtl.record_type <> 'R' AND dtl.record_type <> 'S' AND dtl.record_type <> 'I'
                AND (dtl.dtl_status & 0x0080000000) = 0x0000000000
                AND rvcdef.obj_num IN ('1', '2', '3')
                AND CONVERT(CHAR(12), business_date, 112) = '$fechaConsulta'
    GROUP BY    dsdef.dsvc_seq, dsdef.name, empdef.chk_name, empdef.obj_num, tdtl.business_date

) ORDER BY    BUSINESS_DATE, EMPLOYEE_CHK_NAME";

        // CIERRE DE CAJAS -    TOTALES
        $queryCC = "
		SELECT      CONVERT(CHAR(12), tdtl.business_date, 112)  AS BUSINESS_DATE,
            SUM
            (
                IF(dsdef.obj_num IS NOT NULL)
                THEN -1.0 * dtl.chk_ttl
                ELSE 0.00
                ENDIF
            )                                           AS DONACION,
            SUM(dtl.inclusive_tax_ttl)                  AS TAX_TTL,
            SUM
            (
                IF(dtl.dtl_type IN('D', 'M'))
                THEN dtl.chk_ttl - dtl.inclusive_tax_ttl
                ELSE 0.00
                ENDIF
            ) + DONACION - GROUPON                      AS NET_TTL,
            SUM
            (
                IF(dtl.dtl_type IN('D', 'M'))
                THEN dtl.chk_ttl
                ELSE 0.00
                ENDIF
            ) + DONACION - GROUPON                      AS GROSS_TTL,
            COUNT(DISTINCT cdtl.chk_seq)                AS TRANS_COUNT,
            (
                SELECT  SUM(base_count_amt)  AS COUNTED_TOTAL
                FROM         micros.cm_receptacle_dtl   rdtl
                        JOIN micros.cm_count_dtl        cdtl   ON cdtl.receptacle_seq       = rdtl.receptacle_seq
                        JOIN micros.cm_counted_unit_dtl cudtl  ON cudtl.count_seq           = cdtl.count_seq
                        JOIN micros.cm_count_unit_dtl   ctudtl ON ctudtl.count_item_seq     = cudtl.count_item_seq AND
                                                                  ctudtl.ref_count_unit_seq = cudtl.ref_count_unit_seq
                        JOIN micros.cm_count_item_dtl   idtl   ON idtl.count_item_seq       = ctudtl.count_item_seq
                WHERE   rdtl.open_business_date = BUSINESS_DATE
            )                                           AS COUNTED_TTL,
            COUNTED_TTL - GROSS_TTL                     AS DIFF,
            SUM
            (
                IF(tmdef.obj_num IS NOT NULL AND tmdef.obj_num IN ('17', '19'))
                THEN dtl.chk_ttl
                ELSE 0.00
                ENDIF
            )                                           AS CC_TTL,
            SUM
            (
                IF(tmdef.obj_num IS NOT NULL AND tmdef.obj_num IN ('41','42','43','44','45','46','50','2006','2007','2008','2011','2012','2013','2014','4001','4002','4003','4004'))
                THEN dtl.chk_ttl
                ELSE 0.00
                ENDIF
            )                                           AS COUPON_TTL,
            ROUND(COUPON_TTL*(19.0/(100.0 + 19.0)),0)   AS COUPON_TAX,
            SUM
            (
                IF(tmdef.obj_num IS NOT NULL AND tmdef.obj_num IN ('41','42','43','44','45','46','50','2006','2007','2008','2011','2012','2013','2014','4001','4002','4003','4004'))
                THEN 1
                ELSE 0
                ENDIF
            )                                           AS COUPON_COUNT,
            SUM
            (
                IF(tmdef.obj_num IS NOT NULL AND tmdef.obj_num IN ('2006','2007','2008','4003','4004'))
                THEN dtl.chk_ttl
                ELSE 0
                ENDIF
            )                                           AS GROUPON,
            0.00                                        AS EXEMPT_TTL,
            0.00                                        AS COMPENSATION_TTL
FROM                  micros.chk_dtl   cdtl
                 JOIN micros.trans_dtl tdtl    ON tdtl.chk_seq    = cdtl.chk_seq
                 JOIN micros.dtl       dtl     ON dtl.trans_seq   = tdtl.trans_seq
                 JOIN micros.rvc_def   rvcdef  ON rvcdef.rvc_seq  = cdtl.rvc_seq
            LEFT JOIN micros.tmed_dtl  tmdtl   ON tmdtl.dtl_seq   = dtl.dtl_seq     AND tmdtl.trans_seq = dtl.trans_seq
            LEFT JOIN micros.tmed_def  tmdef   ON tmdef.tmed_seq  = tmdtl.tmed_seq
            LEFT JOIN micros.dsvc_dtl  dsdtl   ON dsdtl.trans_seq = dtl.trans_seq   AND dsdtl.dtl_seq = dtl.dtl_seq AND (dtl.dtl_status & 0x0080000000) = 0x0080000000
            LEFT JOIN micros.dsvc_def  dsdef   ON dsdef.dsvc_seq  = dsdtl.dsvc_seq  AND dsdef.obj_num IN ('11')
WHERE       cdtl.chk_open = 'F'
            AND cdtl.ob_ccs05_chk_cancelled = 'F'
            AND dtl.record_type <> 'R' AND dtl.record_type <> 'S' AND dtl.record_type <> 'I'
            AND rvcdef.obj_num IN ('1', '2', '3')
            AND convert(char(12), tdtl.business_date, 112) ='$fechaConsulta'

GROUP BY    tdtl.business_date
HAVING      GROSS_TTL <> 0.00
ORDER BY    tdtl.business_date";

        //CREDITOS SIN CUPON
        $queryCredSC = "(
		SELECT      CONVERT(CHAR(12), rdtl.open_business_date, 112) AS BUSINESS_DATE,
            tmdef.name                                      AS TENDER_MEDIA,
            SUM(base_count_amt)                             AS TENDER_TOTAL,
            empdef.obj_num                                  AS EMPLOYEE_OBJ_NUM,
            empdef.chk_name                                 AS EMPLOYEE_CHK_NAME,
            (
                SELECT  COUNT(*)
                FROM         micros.chk_dtl   cdtl
                        JOIN micros.rvc_def   rvcdef    ON rvcdef.rvc_seq  = cdtl.rvc_seq
                        JOIN micros.trans_dtl tdtl      ON tdtl.chk_seq    = cdtl.chk_seq
                        JOIN micros.dtl       dtl       ON dtl.trans_seq   = tdtl.trans_seq
                        JOIN micros.tmed_dtl  tmdtl     ON tmdtl.trans_seq = dtl.trans_seq AND tmdtl.dtl_seq = dtl.dtl_seq
                        JOIN micros.tmed_def  tmdef2    ON tmdef2.tmed_seq = tmdtl.tmed_seq
                        JOIN micros.emp_def   empdef2   ON empdef2.emp_seq = cdtl.emp_seq
                WHERE       cdtl.chk_open = 'F'
                        AND cdtl.ob_ccs05_chk_cancelled = 'F'
                        AND dtl.record_type <> 'R' AND dtl.record_type <> 'S' AND dtl.record_type <> 'I'
                        AND rvcdef.obj_num IN ('1', '2', '3')
                        AND tdtl.business_Date = rdtl.open_business_date
                        AND tmdef2.tmed_seq = tmdef.tmed_seq
                        AND empdef2.emp_seq = empdef.emp_seq
            )                                               AS TRANS_COUNT
FROM             micros.cm_receptacle_dtl                     rdtl
            JOIN micros.cm_employee_receptacle_assignment_dtl cmemp    ON cmemp.receptacle_seq   = rdtl.receptacle_seq
            JOIN micros.emp_def                               empdef   ON empdef.emp_seq         =  cmemp.employee_seq
            JOIN micros.cm_count_dtl                          cdtl     ON cdtl.receptacle_seq    = rdtl.receptacle_seq
            JOIN micros.cm_counted_unit_dtl                   cudtl    ON cudtl.count_seq        = cdtl.count_seq
            JOIN micros.cm_count_unit_dtl                     ctudtl   ON ctudtl.count_item_seq  = cudtl.count_item_seq AND ctudtl.ref_count_unit_seq = cudtl.ref_count_unit_seq
            JOIN micros.cm_count_item_dtl                     idtl     ON idtl.count_item_seq    = ctudtl.count_item_seq
            JOIN micros.cm_count_calc_item_dtl                calcitm  ON calcitm.count_item_seq = ctudtl.count_item_seq
            JOIN micros.cm_item_def                           cmitmdef ON cmitmdef.cm_item_seq   = calcitm.component_item_seq
            JOIN micros.tmed_def                              tmdef    ON tmdef.tmed_seq         = cmitmdef.res_item_id AND tmdef.type = 'T'
		WHERE   tmdef.obj_num IN ('41', '42', '43', '44', '45', '50', '2007','2011','2012','2013','2014','4001','4002')
GROUP BY    BUSINESS_DATE, TENDER_MEDIA, EMPLOYEE_OBJ_NUM, EMPLOYEE_CHK_NAME, tmdef.tmed_seq, empdef.emp_seq, open_business_date
HAVING      TENDER_TOTAL <> 0.00 AND CONVERT(CHAR(12), business_date, 112) = '$fechaConsulta'
)
ORDER BY    BUSINESS_DATE, EMPLOYEE_CHK_NAME";

        //TRANSACCIONES - X CAJERO
        $queryTransXCajero = "
		SELECT      convert(char(12), tdtl.business_date, 112)      AS BUSINESS_DATE,
            empdef.obj_num                                  AS EMPLOYEE_OBJ_NUM,
            empdef.chk_name                                 AS EMPLOYEE_CHK_NAME,
            COUNT(distinct cdtl.chk_seq)                    AS TRANS_COUNT

FROM             micros.chk_dtl   cdtl
            JOIN micros.rvc_def   rvcdef    ON rvcdef.rvc_seq  = cdtl.rvc_seq
            JOIN micros.trans_dtl tdtl      ON tdtl.chk_seq    = cdtl.chk_seq
            JOIN micros.emp_def   empdef    ON empdef.emp_seq  = cdtl.emp_seq

WHERE           cdtl.chk_open = 'F'
            AND cdtl.ob_ccs05_chk_cancelled = 'F'
            AND rvcdef.obj_num IN ('1', '2', '3')
            AND CONVERT(CHAR(12), tdtl.business_date, 112) ='$fechaConsulta'

GROUP BY    empdef.chk_name, empdef.obj_num, tdtl.business_date
ORDER BY    BUSINESS_DATE, EMPLOYEE_OBJ_NUM";

        //TOTALES - X CAJERO
        $queryTotalXCajero = "
		SELECT      CONVERT(CHAR(12), tdtl.business_date, 112)      AS BUSINESS_DATE,
            empdef.obj_num                                  AS EMPLOYEE_OBJ_NUM,
            empdef.chk_name                                 AS EMPLOYEE_CHK_NAME,
            SUM
            (
                IF(dsdef.obj_num IS NOT NULL)
                THEN -1.0 * dtl.chk_ttl
                ELSE 0.00
                ENDIF
            )                                               AS DONACION,
            SUM
            (
                IF(tmdef.obj_num IS NOT NULL AND tmdef.obj_num IN ('4003','4004', '4005'))
                THEN dtl.chk_ttl
                ELSE 0
                ENDIF
            )                                               AS GROUPON,
            SUM
            (
                IF(dtl.dtl_type IN('D', 'M'))
                THEN dtl.chk_ttl
                ELSE 0.00
                ENDIF
            ) + DONACION - GROUPON                          AS TENDER_TOTAL,
            COUNT(DISTINCT cdtl.chk_seq)                    AS TRANS_COUNT

FROM                  micros.chk_dtl   cdtl
                 JOIN micros.trans_dtl tdtl    ON tdtl.chk_seq    = cdtl.chk_seq
                 JOIN micros.dtl       dtl     ON dtl.trans_seq   = tdtl.trans_seq
                 JOIN micros.rvc_def   rvcdef  ON rvcdef.rvc_seq  = cdtl.rvc_seq
                 JOIN micros.emp_def   empdef  ON empdef.emp_seq  = cdtl.emp_seq
            LEFT JOIN micros.tmed_dtl  tmdtl   ON tmdtl.dtl_seq   = dtl.dtl_seq     AND tmdtl.trans_seq = dtl.trans_seq
            LEFT JOIN micros.tmed_def  tmdef   ON tmdef.tmed_seq  = tmdtl.tmed_seq
            LEFT JOIN micros.dsvc_dtl  dsdtl   ON dsdtl.trans_seq = dtl.trans_seq   AND dsdtl.dtl_seq = dtl.dtl_seq AND (dtl.dtl_status & 0x0080000000) = 0x0080000000
            LEFT JOIN micros.dsvc_def  dsdef   ON dsdef.dsvc_seq  = dsdtl.dsvc_seq  AND dsdef.obj_num IN ('11')

WHERE       cdtl.chk_open = 'F'
            AND cdtl.ob_ccs05_chk_cancelled = 'F'
            AND dtl.record_type <> 'R' AND dtl.record_type <> 'S' AND dtl.record_type <> 'I'
            AND rvcdef.obj_num IN ('1', '2', '3')
            AND CONVERT(CHAR(12), tdtl.business_date, 112) = '$fechaConsulta'

GROUP BY    tdtl.business_date, empdef.obj_num, empdef.chk_name
		ORDER BY tdtl.business_date, empdef.obj_num, empdef.chk_name";

        //DELIVERY - CAJEROS
        $queryDelivery = "
		SELECT      CONVERT(CHAR(12), tdtl.business_date, 112)      AS BUSINESS_DATE,
            empdef.chk_name                                 AS EMPLOYEE_CHK_NAME,
            SUM(dtl.chk_ttl)                                AS TENDER_TOTAL,
            COUNT(DISTINCT cdtl.chk_seq)                    AS TRANS_COUNT

FROM             micros.chk_dtl cdtl
            JOIN micros.trans_dtl tdtl ON tdtl.chk_seq   = cdtl.chk_seq
            JOIN micros.dtl dtl        ON dtl.trans_Seq  = tdtl.trans_seq
            JOIN micros.emp_def empdef ON empdef.emp_seq = tdtl.trans_emp_seq

WHERE           cdtl.chk_open = 'F'
            AND cdtl.ob_ccs05_chk_cancelled = 'F'
            AND dtl.record_type <> 'R' AND dtl.record_type <> 'S' AND dtl.record_type <> 'I'
            AND tdtl.chk_emp_seq <> tdtl.trans_emp_seq
            AND dtl.dtl_type = 'T' AND dtl.chk_ttl <> 0.00
            AND tdtl.chk_emp_seq = '163'
            AND CONVERT(CHAR(12), tdtl.business_date, 112) = '$fechaConsulta'

GROUP BY    tdtl.business_date, empdef.emp_seq, empdef.chk_name
ORDER BY    tdtl.business_date, empdef.chk_name";

        // CUPONES - GRUPON
        $queryCupones = "
		SELECT  	tdtl.business_date      	AS BUSINESS_DATE,
	        midef.obj_num           	AS PLU,

 	        'Groupon'
       					AS TENDER_MEDIA,
	        SUM(dtl.chk_ttl)        	AS TENDER_TOTAL,
	        empdef.obj_num          	AS EMPLOYEE_OBJ_NUM,
	        empdef.chk_name         	AS EMPLOYEE_CHK_NAME,
	        COUNT()                 	AS TRANS_COUNT

FROM		     micros.chk_dtl   cdtl
	        JOIN micros.rvc_def   rvcdef    ON rvcdef.rvc_seq  = cdtl.rvc_seq
       	 	JOIN micros.trans_dtl tdtl      ON tdtl.chk_seq    = cdtl.chk_seq
	        JOIN micros.dtl       dtl       ON dtl.trans_seq   = tdtl.trans_seq
       	 	JOIN micros.mi_dtl    midtl     ON midtl.trans_seq = dtl.trans_seq AND midtl.dtl_seq = dtl.dtl_seq
	        JOIN micros.mi_def    midef     ON midef.mi_seq    = midtl.mi_seq
       	 	JOIN micros.emp_def   empdef    ON empdef.emp_seq  = cdtl.emp_seq

WHERE           cdtl.chk_open = 'F'
	        AND cdtl.ob_ccs05_chk_cancelled = 'F'
            AND dtl.record_type <> 'R' AND dtl.record_type <> 'S' AND dtl.record_type <> 'I'
	        AND rvcdef.obj_num IN ('1', '2', '3')
	        AND midef.obj_num IN ('10533', '10032')
		 	AND CONVERT(CHAR(12), business_date, 112) ='$fechaConsulta'
GROUP BY midef.mi_seq, midef.obj_num, midef.name_1, empdef.chk_name, empdef.obj_num, tdtl.business_date";

//VENTA POR MEDIOS
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
				AND CONVERT(CHAR(12), tdtl.business_date, 112) ='$fechaConsulta'
		GROUP BY    tdtl.business_date, RVC, ORDER_TYPE
		HAVING      GROSS_TTL <> 0.00";

    }
    // VENTA Z
    $queryVentaZ = "
		SELECT sum (daytotalsales) TOTAL_VENTA,
			SUM(FCRENDINVNUMBER-FCRSTARTINVNUMBER) AS TRANS
		FROM Fiscal.fcr_z_info
		WHERE MICROSBSNZDATE='$fechaConsulta'";

    // ANULACIONES
    $queryAnulacion = "
		SELECT subtotal12 as TOTAL_BRUTO,
			taxttl1 AS IVA,
			FCRINVNUMBER as BOLETA,
			1 TRANS
		FROM micros.fcr_invoice_data
		WHERE INVOICESTATUS=0 AND subtotal12 IS NOT NULL AND MICROSBSNZDATE ='$fechaConsulta'";

    // HORAS TRABAJADAS CAJEROS
    $queryHorasCajeros = "
		SELECT CONVERT(CHAR(12), tdtl.business_date, 112)      				AS BUSINESS_DATE,
            		empdef.obj_num                                  				AS EMPLOYEE_OBJ_NUM,
            		empdef.chk_name                                 				AS EMPLOYEE_CHK_NAME,
          		empdef.payroll_id                               				AS EMPLOYEE_id,
         		datediff(hh, max(chk_clsd_date_time), min(chk_open_date_time))*-1	AS HORAS_CAJAS
		FROM	micros.chk_dtl   cdtl
              JOIN micros.trans_dtl tdtl    ON tdtl.chk_seq    = cdtl.chk_seq
              JOIN micros.dtl       dtl     ON dtl.trans_seq   = tdtl.trans_seq
              JOIN micros.rvc_def   rvcdef  ON rvcdef.rvc_seq  = cdtl.rvc_seq
              JOIN micros.emp_def   empdef  ON empdef.emp_seq  = cdtl.emp_seq
            	LEFT JOIN micros.tmed_dtl  tmdtl   ON tmdtl.dtl_seq   = dtl.dtl_seq     AND tmdtl.trans_seq = dtl.trans_seq
            	LEFT JOIN micros.tmed_def  tmdef   ON tmdef.tmed_seq  = tmdtl.tmed_seq
            	LEFT JOIN micros.dsvc_dtl  dsdtl   ON dsdtl.trans_seq = dtl.trans_seq   AND dsdtl.dtl_seq = dtl.dtl_seq AND (dtl.dtl_status & 0x0080000000) = 0x0080000000
            	LEFT JOIN micros.dsvc_def  dsdef   ON dsdef.dsvc_seq  = dsdtl.dsvc_seq  AND dsdef.obj_num IN ('11')
		WHERE	cdtl.chk_open = 'F'
            		AND dtl.record_type <> 'R' AND dtl.record_type <> 'S' AND dtl.record_type <> 'I'
            		AND CONVERT(CHAR(12), tdtl.business_date, 112) = '$fechaConsulta'
		GROUP BY    tdtl.business_date, empdef.payroll_id,empdef.obj_num, empdef.chk_name  ";

    /////////////////////////CONSULTAR INFORMACI�N//////////////////////
    //VENTAS POR PRODUCTO
    $result = odbc_exec($dbh,$queryXProducto,false) or die('error');
    $i = 0;
    while ($row = odbc_fetch_object($result)) {
        $lc_regs['XProducto'][$i] = array(
            "FECHA" => $row->BUSINESS_DATE,
            "NUM_PLU" => $row->PLU,
            "CANTIDAD" => $row->MI_COUNT,
            "VALOR" => $row->MI_TOTAL,
            "EXEMPT_TTL" => $row->EXEMPT_TTL,
            "BASEIVA" => $row->BASE_TTL,
            "NETA_TOTAL" => $row->NET_TTL,
            "IVA_TOTAL" => $row->TAX_TTL,
            "BASEIVA_UNIT" => $row->UNIT_BASE_TTL,
            "BASECERO_UNIT" => $row->UNIT_ZERO_BASE_TTL,
            "NETA_UNIT" => $row->UNIT_NET_TTL,
            "IVA_UNIT" => $row->UNIT_TAX,
            "BRUTA_UNIT" => $row->UNIT_GROSS_TTL,
            "DESCUENTOS" => $row->DISCOUNT,
            "SERVICIOS" => $row->SERVICES,
            "RVC" => $row->ORDER_TYPE,
            "CENTER" => $row->RVC,
        );
        $i++;
    }


    $jsXProducto = (json_encode($lc_regs));
    odbc_free_result($result);
    if ($jsXProducto != "null") {
        //VENTAS POR HORA
        $result = odbc_exec($dbh, $queryXHora, false) or die('error');
        $lc_regs = null;
        $i = 0;
        while ($row = odbc_fetch_object($result)) {
            $lc_regs['XHora'][$i] = array(
                "FECHA" => $row->BUSINESS_DATE,
                "TIEMPO" => $row->PERIOD_HOUR,
                "VALOR" => $row->TENDER_TOTAL,
                "TRANSACCION" => $row->TRANS_COUNT);
            $i++;
        }

        $jsXHora = (json_encode($lc_regs));
        odbc_free_result($result);

        //CIERRE DE CAJAS
        $result = odbc_exec($dbh, $queryCC, false) or die('error');
        $lc_regs = null;
        $i = 0;
        while ($row = odbc_fetch_object($result)) {
            $lc_regs['CierreCajas'][] = array(
                "FECHA" => $row->BUSINESS_DATE,
                "IVA" => $row->TAX_TTL,
                "SERVICIO" => $row->SERVICE_TTL,
                "VENTA_NETA" => $row->NET_TTL,
                "VENTA_BRUTA" => $row->GROSS_TTL,
                "TRANSACCIONES" => $row->TRANS_COUNT,
                "COUNTED_TTL" => $row->COUNTED_TTL,
                "SOBRANTE" => $row->DIFF,
                "CC_TTL" => $row->CC_TTL,
                "PVP_CUPON" => $row->COUPON_TTL,
                "IVA_CUPON" => $row->COUPON_TAX,
                "CANTIDAD_CUPON" => $row->COUPON_COUNT,
                "EXEMPT_TTL" => $row->EXEMPT_TTL,
                "COMPENSACION" => $row->COMPENSATION_TTL);
            $i++;
        }

        $jsCC = (json_encode($lc_regs));
        odbc_free_result($result);

        //VENTA POR CAJERO
        $result = odbc_exec($dbh, $queryXCajero, $dbh, false) or die('error');
        $lc_regs = null;
        $i = 0;
        while ($row = odbc_fetch_object($result)) {
            $lc_regs['XCajero'][$i] = array(
                "FECHA" => $row->BUSINESS_DATE,
                "FORMA_PAGO" => $row->TENDER_MEDIA,
                "VALOR" => $row->TENDER_TOTAL,
                "CAJERO" => $row->EMPLOYEE_CHK_NAME,
                "TRANSACCIONES" => $row->TRANS_COUNT);
            $i++;
        }
        $jsXCajero = (json_encode($lc_regs));
        odbc_free_result($result);

        //VENTAS CREDITOS SC
        $result = odbc_exec($dbh, $queryCredSC, false) or die('error');
        $lc_regs = null;
        $i = 0;
        while ($row = odbc_fetch_object($result)) {
            $lc_regs['XcreditoSC'][$i] = array(
                "FECHA" => $row->BUSINESS_DATE,
                "FORMA_PAGO" => $row->TENDER_MEDIA,
                "VALOR" => $row->TENDER_TOTAL,
                "CAJERO" => $row->EMPLOYEE_CHK_NAME,
                "TRANSACCIONES" => $row->TRANS_COUNT);
            $i++;
        }
        $jsCreditoSC = (json_encode($lc_regs));
        odbc_free_result($result);

        //TRANSACCIONES POR CAJERO
        $result = odbc_exec($dbh, $queryTransXCajero, false) or die('error');
        $lc_regs = null;
        $i = 0;
        while ($row = odbc_fetch_object($result)) {
            $lc_regs['TransXCajero'][$i] = array(
                "FECHA" => $row->BUSINESS_DATE,
                "CAJERO" => $row->EMPLOYEE_CHK_NAME,
                "TRANSACCIONES" => $row->TRANS_COUNT);
            $i++;
        }
        $jsTransXCajero = (json_encode($lc_regs));
        odbc_free_result($result);

        //TOTALES POR CAJERO
        $result = odbc_exec($dbh, $queryTotalXCajero, false) or die('error');
        $lc_regs = null;
        $i = 0;
        while ($row = odbc_fetch_object($result)) {
            $lc_regs['TotalXCajero'][$i] = array(
                "FECHA" => $row->BUSINESS_DATE,
                "VALOR" => $row->TENDER_TOTAL,
                "CAJERO" => $row->EMPLOYEE_CHK_NAME,
                "TRANSACCIONES" => $row->TRANS_COUNT);
            $i++;
        }
        $jsTotalXCajero = (json_encode($lc_regs));
        odbc_free_result($result);

        //CAJEROS DELIVERY
        $result = odbc_exec($dbh, $queryDelivery, false) or die('error');
        $lc_regs = null;
        $i = 0;
        while ($row = odbc_fetch_object($result)) {
            $lc_regs['DeliveryXCajero'][$i] = array(
                "FECHA" => $row->BUSINESS_DATE,
                "VALOR" => $row->TENDER_TOTAL,
                "CAJERO" => $row->EMPLOYEE_CHK_NAME,
                "TRANSACCIONES" => $row->TRANS_COUNT);
            $i++;
        }
        $jsDeliveryXCajero = (json_encode($lc_regs));
        odbc_free_result($result);

        //CUPONES - GRUPON
        $result = odbc_exec($dbh, $queryCupones, false) or die('error');
        $lc_regs = null;
        $i = 0;
        while ($row = odbc_fetch_object($result)) {
            $lc_regs['Cupones'][$i] = array(
                "FECHA" => $row->BUSINESS_DATE,
                "PLU" => $row->PLU,
                "MI_NAME" => $row->MI_NAME,
                "TENDER_MEDIA" => $row->TENDER_MEDIA,
                "TENDER_TOTAL" => $row->TENDER_TOTAL,
                "EMPLOYEE_OBJ_NUM" => $row->EMPLOYEE_OBJ_NUM,
                "EMPLOYEE_CHK_NAME" => $row->EMPLOYEE_CHK_NAME,
                "TRANS_COUNT" => $row->TRANS_COUNT);
            $i++;
        }
        $jsCupones = (json_encode($lc_regs));
        odbc_free_result($result);

        // VENTA Z
        $result = odbc_exec($dbh, $queryVentaZ, false) or die('error');
        $lc_regs = null;
        $i = 0;
        while ($row = odbc_fetch_object($result)) {
            $lc_regs['VentaZ'][$i] = array(
                "TOTAL_VENTA" => $row->TOTAL_VENTA,
                "TRANSACCIONES" => $row->TRANS,
            );
            $i++;
        }
        $jsVentaZ = (json_encode($lc_regs));
        odbc_free_result($result);

        // ANULACIONES
        $result = odbc_exec($dbh, $queryAnulacion, false) or die('error');
        $lc_regs = null;
        $i = 0;
        while ($row = odbc_fetch_object($result)) {
            $lc_regs['Anuladas'][$i] = array(
                "TOTAL_ANULADO" => $row->TOTAL_BRUTO,
                "IVA" => $row->IVA,
                "BOLETA" => $row->BOLETA,
                "TRANSACCIONES" => $row->TRANS,
            );
            $i++;
        }
        $jsAnulada = (json_encode($lc_regs));
        odbc_free_result($result);

        // HORAS TRABAJADAS POR CAJEROS
        $result = odbc_exec($dbh, $queryHorasCajeros, false) or die('error');
        $lc_regs = null;
        $i = 0;

        while ($row = odbc_fetch_object($result)) {
            $lc_regs['HorasCajeros'][$i] = array(
                "CODIGO" => $row->EMPLOYEE_OBJ_NUM,
                "NOMBRE" => $row->EMPLOYEE_CHK_NAME,
                "RUT" => $row->EMPLOYEE_id,
                "HORAS_CAJAS" => $row->HORAS_CAJAS,
            );
            $i++;
        }
        $jsHoraCajeros = (json_encode($lc_regs));
        odbc_free_result($result);

        //VENTA POR MEDIOS
        $result = odbc_exec($dbh, $queryVtaMedios, false) or die('error');
        $lc_regs = null;
        $i = 0;
        while ($row = odbc_fetch_object($result)) {
            $lc_regs['VtaMedios'][$i] = array(
                "ORDER_TYPE" => $row->ORDER_TYPE,
                "NET_TTL" => $row->NET_TTL,
                "GROSS_TTL" => $row->GROSS_TTL,
                "TRANS_COUNT" => $row->TRANS_COUNT,
                "CENTER" => $row->RVC,
            );
            $i++;
        }
        $jsVtaMedios = (json_encode($lc_regs));
        odbc_free_result($result);

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
        odbc_close($dbh);
        if ($lc_inter->ingresarInformacion('jsonInformacion', $lc_condicion_e)) {
            $lc_rowdatos = $lc_inter->fn_leerobjeto();
            $mensaje = $lc_rowdatos->mensaje;
            $total = $lc_rowdatos->total;
            $errores = $lc_rowdatos->errores;
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
        odbc_close($dbh);?>
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
}

?>
