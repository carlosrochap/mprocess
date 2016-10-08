<?php
require_once( 'db/db.inc.php' ) ;

class Mysql extends DBBase 
{
	var $debug ;
	var $values ;
	
	function __construct( $db_loc_db = null , $db_user = null , $db_pwd = null )
	{
		parent::__construct( $db_loc_db , $db_user , $db_pwd ) ;
		$this->debug = false ;
		$this->values = array();
	}
	
	function query( $sql )
	{
		#Reconnect to the db just in case the connection was dropped
		$this->reconnect();
		
		if( $this->debug )
			$this->showMsg( $sql ) ;
			
		if( !$rs = mysql_query( $sql , $this->link ) )
		{
			$msg = sprintf( "The query %s failed ( %s : %s ) " , $sql , mysql_errno( $this->link ) , mysql_error( $this->link ) ) ;
			$this->showMsg( $msg ); 
			$this->showMsg( "Terminating....." ) ;
			exit();
		}
	
		return $rs ;
	}
	
	function insert( $tbl , $values )
	{
		$str_f = '' ;
		$str_v = '' ;
		
		foreach( $values as $key => $val )
		{
			$val = addslashes( $val ) ;
			$str_f .= "`{$key}`," ;
			$str_v .= "'{$val}'," ;
		}

		$str_v = rtrim( $str_v , ',' ) ;
		$str_f = rtrim( $str_f , ',' ) ;
		
		$sql = sprintf( "INSERT INTO %s ( %s ) VALUES( %s ) " , $tbl , $str_f , $str_v  ) ;
		$this->query( $sql ) ;
		( $this->debug ) ? $this->showMsg( "Success Insert" ) : '' ;	
	}
	
	function batch_insert( $tbl , $values )
	{
		$str_f = '' ;
		$str_v = '' ;
		
		foreach( $values as $key_arr )
		{
			foreach( $key_arr as $key => $val )
			{
				$val = addslashes( $val ) ;
				$str_f .= "`{$key}`," ;
				$str_v .= "'{$val}'," ;
			}
			
			$str_v = rtrim( $str_v , ',' ) ;
			$str_f = rtrim( $str_f , ',' ) ;
		}

		
		$sql = sprintf( "INSERT INTO %s ( %s ) VALUES( %s ) " , $tbl , $str_f , $str_v  ) ;
		$this->query( $sql ) ;
		( $this->debug ) ? $this->showMsg( "Success Insert" ) : '' ;	
	}
	
	function update( $tbl , $values , $condition )
	{
		$str_val = '' ;
		foreach( $values as $key => $val )
		{
			$val = addslashes( $val ) ;
			$str_val .= "`{$key}`='{$val}'," ;
		}
		
		$str_val = rtrim( $str_val , ',' ) ;
		if( is_null( $condition ) )
			$sql = sprintf( "UPDATE %s SET %s " , $tbl , $str_val ) ;
		else
			$sql = sprintf( "UPDATE %s SET %s WHERE %s" , $tbl , $str_val , $condition ) ;
		$this->query( $sql ) ;
		
		( $this->debug ) ? $this->showMsg( "Success Update" ) : '' ;	
	}
	
	function multiQuery( $sql )
	{
		$result = $this->query( $sql ) ;
		$rs = array() ;
		while( $row = mysql_fetch_assoc( $result ) )
		{
			$rs[ ] = $row ;
		}
		
		mysql_free_result( $result ) ;
		return $rs ;
	}
	
	function oneQuery( $sql )
	{
		$sql .= " LIMIT 1" ;
		$result = $this->query( $sql ) ;
		$rs = mysql_fetch_array( $result ) ;
		mysql_free_result( $result ) ;
		return $rs ;
	}
	
	function runQuery( $sql )
	{
		return $this->query( $sql) ;
	}
	
	function getNumRows( $obj_res )
	{
		return mysql_num_rows( $obj_res ) ;
	}
	
	function fetchData( $obj_res )
	{
		return mysql_fetch_assoc( $obj_res ) ;
	}
}
?>