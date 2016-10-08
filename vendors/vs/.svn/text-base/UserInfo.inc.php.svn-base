<?php
include_once( 'db/db.config.php') ;
include_once( 'db/mysql.inc.php') ;

class UserInfo
{
	var $mysql ;
	var $size ;
	var $container ;
	var $user_info ;
	var $debug ;
	var $gender ;
	
	function __construct( $gender = 'F' , $mysql = "72.10.166.28/gamma" ,  $debug = false ) 
	{
		$this->mysql = $mysql ;
		$this->container = array() ;
		$this->user_info = array() ;
		$this->debug = false ;
		$this->gender = $gender ;
				
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
	
	function getUserInfo( $username )
	{
		return $this->user_info[ $username ] ;	
	}
	
	private function buildContainer()
	{
		$sql = "SELECT * FROM username_info WHERE gender = '{$this->gender}'" ;
		$rs = $this->mysql->multiQuery( $sql ) ;
		$this->mysql->close();
		
		foreach( $rs as $key )
		{
			array_push( $this->container , $key ) ;	
			$this->user_info[ $key['username'] ] = $key ;
		}
		
		$this->size = count( $this->container ) ;
	}
	
}
?>