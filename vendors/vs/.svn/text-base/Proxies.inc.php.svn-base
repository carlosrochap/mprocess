<?php
include_once( 'db/db.config.php') ;
include_once( 'db/mysql.inc.php') ;

class Proxies
{
	var $mysql ;
	var $size ;
	var $container ;
	var $id ;
	var $debug ;
	var $table ;
	var $cache ;
	
	function __construct( $id = 6 , $mysql = "72.10.166.22:3309/gamma" , $debug = false ) 
	{
		$this->mysql = $mysql ;
		$this->id = $id ;
		$this->container = array() ;
		$this->cache = array() ;
		$this->debug = $debug ;
		$this->table = "gamma_proxies" ;
				
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
//		if( count( $this->cache ) == 0 )
//		{
//			$this->cache = $this->container ;
//			shuffle( $this->cache ) ;
//		}	
		
		return  $this->container[ mt_rand( 0, $this->size - 1) ] ;
//		return  array_pop( $this->cache ) ;
	}
	
	function buildContainer()
	{
		$sql = "SELECT proxies FROM ".$this->table." WHERE id = ".$this->id ;
		list( $rs ) = $this->mysql->OneQuery( $sql ) ;
		$this->mysql->close();
		$rs = explode( "\n" , $rs ) ;
		foreach( $rs as $key )
		{
			array_push( $this->container , trim( $key ) ) ;
		}
		
		$this->cache = $this->container ;
		shuffle( $this->cache ) ;
		$this->size = count( $this->container ) ;
	}
}
?>