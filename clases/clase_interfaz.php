<?php

class interfaz extends sql {

    //public static $query;

    public function __construct() {
        parent::__construct();
    }

    public function validacionesGenerales($lc_opcion, $lc_condiciones) {
        switch ($lc_opcion) {
            case 'Configuracion_Pixel' :
                $lc_query = "set dateformat dmy "
                    . "SELECT DISTINCT cr.*, Cod_Tienda, dbo.fn_div_iva('$lc_condiciones[1]') as div_IVA, dbo.fn_mul_iva('$lc_condiciones[1]') as mul_iva,
			r.cod_cadena
                    FROM dbo.Config_Registradora cr
                    inner join Restaurante r on cr.Cod_Restaurante=r.Cod_Restaurante 
                    where cr.cod_restaurante=$lc_condiciones[0] AND cr.estado=1	";
                return $this->fn_ejecutarquery($lc_query);
            case 'validaGeneracion':
                $lc_query = "SET DATEFORMAT dmy  EXEC Interfaces_ValidaGeneracion " . $lc_condiciones[0] . ", '" . $lc_condiciones[1] . "'";
                return $this->fn_ejecutarquery($lc_query);
        }
    }

    public function ingresarInformacion($lc_opcion, $lc_condiciones) {
        switch ($lc_opcion) {
            case 'CabeceraMix' :
                $lc_query = "SET DATEFORMAT dmy  EXEC Interfaces_CabeceraMix " . $lc_condiciones[0] . ", '" . $lc_condiciones[1] . "', " . $lc_condiciones[2];
                return $this->fn_ejecutarquery($lc_query);
            case 'jsonInformacion' :
                $lc_query = "SET DATEFORMAT dmy  EXEC InterfaceMicros_Nuevo 	 $lc_condiciones[0],		'$lc_condiciones[1]',	'$lc_condiciones[2]', 
											'$lc_condiciones[3]', 	'$lc_condiciones[4]', 	'$lc_condiciones[5]', 
											'$lc_condiciones[6]',	'$lc_condiciones[7]', 	'$lc_condiciones[8]', 
											'$lc_condiciones[9]',	'$lc_condiciones[10]', 	'$lc_condiciones[11]',
											'$lc_condiciones[12]',	'$lc_condiciones[13]',	'$lc_condiciones[14]',
											'$lc_condiciones[16]'";
                return $this->fn_ejecutarquery($lc_query);
        }
    }

   public function ingresarInformacionMedios($lc_opcion, $lc_condiciones) {
        switch ($lc_opcion) {
            case 'jsonInformacion' :
                $lc_query = "SET DATEFORMAT dmy  EXEC [InterfaceMicros_Aregador] $lc_condiciones[0], '$lc_condiciones[3]', '$lc_condiciones[16]'";
                return $this->fn_ejecutarquery($lc_query);
        }
    }


    public function consultas($lc_opcion, $lc_condiciones) {
        switch ($lc_opcion) {
            case 'ventaCajero' :
                $lc_query = "SET DATEFORMAT dmy "
                        . "SELECT Valor,Num_Trans, Cod_Cajero "
                        . "FROM DinTrans "
                        . "WHERE Cod_Restaurante = $lc_condiciones[0] and Cod_Cierre= '$lc_condiciones[2]' and "
                        . "Fecha= '$lc_condiciones[1]' and Cod_FormaPago= ( SELECT Cod_FormaPago FROM FormasPago AS fp "
                        . " INNER JOIN restaurante AS r ON fp.cod_cadena = r.cod_cadena "
                        . "WHERE fp.estado=1 AND cod_restaurante=$lc_condiciones[0] AND nombre LIKE 'TOTAL')  ORDER BY Cod_Cajero";
                return $this->fn_ejecutarquery($lc_query);
            case 'ventasHora' :
                $lc_query = "SET DATEFORMAT dmy  "
                        . "SELECT Tiempo, Transacciones, Valor FROM VentasHora "
                        . "WHERE Cod_Restaurante =$lc_condiciones[0]  and Fecha= '$lc_condiciones[1]'";
                return $this->fn_ejecutarquery($lc_query);
        }
    }

}
