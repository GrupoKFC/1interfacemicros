<?php @session_start();
//////////////////////////////////////////////////////////////
////////DESARROLLADO POR: Ximena Celi/////////////////////////
////////DESCRIPCION: Clase que permite la conexi�n con ///////
///////////////////  la base de datos                 ////////
///////TABLAS INVOLUCRADAS: No hay tablas solo exite  ////////
///////////////////        la base de datos en SQLServer2005//
///////FECHA CREACION: 15-04-2009/////////////////////////////
///////FECHA ULTIMA MODIFICACION: 28-04-2009 /////////////////
///////USUARIO QUE MODIFICO: Ximena Celi /////////////////////
///////DECRIPCION ULTIMO CAMBIO: Renombrar datosd con los ////
//////////////////////////////// est�ndares dados ////////////
//////////////////////////////////////////////////////////////

//Clase para realizar la conexi�n
class conexion{
                private $lc_host;
                private $lc_base;
                private $lc_user;
                private $lc_clave;
	            private $lc_conec;
//Constructor de la clase	
	public function __construct()
	{	 
	  $this->lc_host = "192.168.101.29\PRYSIR";//"192.168.148.64";
	  $this->lc_base = "sql_sir_new";//"SQLGerente_22";
	  $this->lc_user =  "dev_carlos";//"conexion_gerente";
         $this->lc_clave = "Dev#3698";//"gerente*759"; 
	  $this->lc_conec = NULL;	  
	}	
//Funci�n que permite conectarse a la base de datos
	public function fn_conectarse()
	{   
		$serverName = $this->lc_host; //serverName\instanceName
		$connectionInfo = array( "Database"=> $this->lc_base, "UID"=> $this->lc_user, "PWD"=> $this->lc_clave);
		$conn = sqlsrv_connect( $serverName, $connectionInfo);

		if( $conn ) {
		      return $this->lc_conec = $conn;
		}else{
		     echo "Conexión no se pudo establecer.<br />";
		     die( print_r( sqlsrv_errors(), true));
		}	  
	}
	/*public function fn_conectarse()
	{ 
	  if (is_null($this->lc_conec))
	  {
	   if (!($this->lc_conec = mssql_connect($this->lc_host, $this->lc_user, $this->lc_clave)
			  or die ("ERROR!! al intentar conectarse con la base de datos")))
			  $this->fn_errorconec();	
		  elseif (!(mssql_select_db($this->lc_base, $this->lc_conec)))
			 $this->fn_errorconec();	    
	  }
	  return $this->lc_conec;	  
	}*/
//Generar un error en caso de que no se pueda realizar la conexi�n
	private function fn_errorconec()
	{
	  return sqlsrv_errors();
	}
//Funci�n que permite desconectarse a la base de datos
	public function fn_cerrarconec()
	{
	  	if(sqlsrv_close($this->lc_conec))
		 return true;
		else
		   return false;
	}
  }//FIN DE LA CLASE CONEXION
?>
