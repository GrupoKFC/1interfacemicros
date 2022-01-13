<?php

class micros extends sql {

    //public static $query;

    public function __construct() {
        parent::__construct();
    }

    public function actualizaHost($nombre, $codRestaurante, $ip) {
                $lc_query = "update Config_Registradora 
				set Nombre_ODBC = '$nombre'
				where Cod_Restaurante = $codRestaurante and Estado = 1 and IP_Ftp = '$ip'";
                $this->fn_ejecutarquery($lc_query);
       
    }
}
