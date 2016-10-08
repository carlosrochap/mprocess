<?php
include_once( 'db/db.config.php') ;
include_once( 'db/mysql.inc.php') ;

class YahooID
{
	var $mysql ;
	var $table ;
	var $project ;
	
	function __construct( $project , $mysql , $debug = false ) 
	{
		$this->mysql = $mysql ;
		$this->project = ucfirst( $project ) ;
		$this->debug = $debug ;
		$this->table = "yahoo_id" ;
		
		if( !is_resource( $mysql ) )
		{
			$mysql = new Mysql( $mysql );
			$mysql->debug = $this->debug ;
			$mysql->connect();
			$this->mysql = $mysql ;
		}
	}
		
	function flag( $id )
	{
		$sql = "UPDATE ".$this->table." SET assigned_to = concat(assigned_to, ';" . addslashes($this->project) ."' ) WHERE id= ". $id ;
		$this->mysql->query( $sql ) ;
	}
}
?>