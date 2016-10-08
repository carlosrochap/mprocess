<?php
include_once( 'db/db.config.php') ;
include_once( 'db/mysql.inc.php') ;

class DomainList
{
	var $mysql ;
	var $size ;
	var $container ;
	var $id ;
	var $debug ;
	var $table ;
	
	function __construct( $id , $mysql , $debug = false ) 
	{
		$this->mysql = $mysql ;
		$this->id = $id ;
		$this->container = array() ;
		$this->debug = $debug ;
		$this->table = "domains_list" ;
				
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
	
	function buildContainer()
	{
		$sql = "SELECT domains FROM ".$this->table." WHERE id = ".$this->id ;
		list( $rs ) = $this->mysql->OneQuery( $sql ) ;
		$this->mysql->close();
		
		$rs = explode( "\n" , $rs ) ;
		foreach( $rs as $key )
		{
			array_push( $this->container , trim( $key ) ) ;
		}
		
		$this->size = count( $this->container ) ;
	}
}

?>