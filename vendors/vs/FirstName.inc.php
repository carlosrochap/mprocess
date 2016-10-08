<?php
include_once( 'db/db.config.php') ;
include_once( 'db/mysql.inc.php') ;

class FirstName
{
	var $mysql ;
	var $size ;
	var $container ;
	var $gender ;
	var $debug ;
	
	function __construct( $mysql , $gender = NULL , $debug = false ) 
	{
		$this->mysql = $mysql ;
		$this->gender = $gender ;
		$this->container = array() ;
		$this->debug = $debug ;
		
		if( !is_resource( $mysql ) )
		{
			$mysql = new Mysql( $mysql );
			$mysql->debug = $this->debug ;
			$mysql->connect();
			$this->mysql = $mysql ;
		}
		
		$this->buildContainer() ;
	}
	
	function get()
	{
		return  $this->container[ mt_rand( 0, $this->size - 1) ] ;
	}
	
	private function buildContainer()
	{
		$sql = "SELECT firstname FROM extra_firstname " ;
		( !is_null( $this->gender ) || ( $this->gender != 'A' ) ) ? $sql .= "WHERE gender = '{$this->gender}'" : '' ;
		
		$rs = $this->mysql->multiQuery( $sql ) ;
		$this->mysql->close();
		
		foreach( $rs as $key )
		{
			$this->container[] = $key['firstname'] ;	
		}
		
		$this->size = count( $this->container ) ;
	}
	
}
?>