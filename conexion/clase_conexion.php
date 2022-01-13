<?php @session_start();
//////////////////////////////////////////////////////////////
////////DESARROLLADO POR: Ximena Celi/////////////////////////
////////DESCRIPCION: Clase que permite la conexión con ///////
///////////////////  la base de datos                 ////////
///////TABLAS INVOLUCRADAS: No hay tablas solo exite  ////////
///////////////////        la base de datos en SQLServer2005//
///////FECHA CREACION: 15-04-2009/////////////////////////////
///////FECHA ULTIMA MODIFICACION: 28-04-2009 /////////////////
///////USUARIO QUE MODIFICO: Ximena Celi /////////////////////
///////DECRIPCION ULTIMO CAMBIO: Renombrar datosd con los ////
//////////////////////////////// estándares dados ////////////
//////////////////////////////////////////////////////////////

//Clase para realizar la conexión
class conexion{
                private $lc_host;
                private $lc_base;
                private $lc_user;
                private $lc_clave;
	            private $lc_conec;
//Constructor de la clase	
	public function __construct()
	{	 
	  $this->lc_host = "192.168.148.64";
	  $this->lc_base = "SQLGerente_22";
	  $this->lc_user =  "conexion_gerente";
         $this->lc_clave = "gerente*759"; 
	  $this->lc_conec = NULL;  
	}	
//Función que permite conectarse a la base de datos
	public function fn_conectarse()
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
//Generar un error en caso de que no se pueda realizar la conexión
	private function fn_errorconec()
	{
	  return mssql_error();
	}
//Función que permite desconectarse a la base de datos
	public function fn_cerrarconec()
	{
	  	if(mssql_close($this->lc_conec))
		 return true;
		else
		   return false;
	}
  }//FIN DE LA CLASE CONEXION
?>
