<?php
/////////////////////////////////////////////////////////////////////
////////DESARROLLADO POR: Ximena Celi////////////////////////////////
////////DESCRIPCION: Clase que permite realizar algunas /////////////
///////////////////  sentencias SQL (sentencia inicial SELECT) //////
///////TABLAS INVOLUCRADAS: Diferentes de acuerdo a la consulta /////
///////FECHA CREACION: 20-04-2009////////////////////////////////////
///////FECHA ULTIMA MODIFICACION: 04-05-2009/////////////////////////
///////USUARIO QUE MODIFICO: Ximena Celi/////////////////////////////
///////DECRIPCION ULTIMO CAMBIO: Creación de la función  ////////////
////fn_numcampo (devuelve el numero de campos de un aconsulta sql) //
/////////////////////////////////////////////////////////////////////
include("clase_conexion.php");
//Clase para realizar las diferentes sentencias SQL
class sql {
	 private $lc_conec;
	 private $lc_datos;
	 
	 //constructor de la clase  
	 function __construct()
	 {
		 //if(isset($lc_usuario))$lc_usuario=$lc_usuario; else $lc_usuario=NULL; 
	    $this -> lc_conec    = new conexion();
		$this -> lc_datos    = NULL;
		
	 }
	 //funcion que permite armar la sentencia sql
	 public function fn_ejecutarquery($lc_query)
		{  
			
			if($lc_conec = $this->lc_conec->fn_conectarse())
			 {
			   if($this->lc_datos=mssql_query ($lc_query,$lc_conec))
				     return $this->lc_datos;
				else
				   return false;
		      } 	
		}
	//funcion  devuelve dataset por objeto
	public function fn_leerobjeto()
	{ 
	   return mssql_fetch_object($this->lc_datos);
	}
    //funcion  devuelve dataset por arreglo
	public function fn_leerarreglo()
	{ 
	   return  mssql_fetch_array($this->lc_datos);
	}
	//devolvuelve el numero de registros
    public function fn_numregistro()
	{
	  return mssql_num_rows($this->lc_datos);
	} 
		//devuelve el numero de campos de un aconsulta sql
    public function fn_numcampo()
	{
	  return mssql_num_fields($this->lc_datos);
	} 
	//liberar consulta y conexion es decir los recursos que esta utilizando
	public function fn_liberarecurso()
	{
	  @mssql_free_result($this->lc_datos);
	  $this->lc_conec->fn_cerrarconec();
	}
	
 } 
 ?>