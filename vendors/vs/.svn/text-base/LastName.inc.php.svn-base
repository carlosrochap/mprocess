<?php
include_once( 'db/db.config.php') ;
include_once( 'db/mysql.inc.php') ;

class LastName
{
	var $mysql ;
	var $size ;
	var $container ;
	var $debug ;
	
	function __construct( $mysql , $debug = false ) 
	{
		$this->mysql = $mysql ;
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
		$sql = "SELECT lastname FROM extra_lastname " ;
		$rs = $this->mysql->multiQuery( $sql ) ;
		$this->mysql->close();
		foreach( $rs as $key )
		{
			$this->container[] = $key['lastname'] ;	
		}
		
		$this->size = count( $this->container ) ;
	}
}
?>