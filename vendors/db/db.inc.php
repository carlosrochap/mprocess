<?php
require_once( 'db/db.config.php') ;

class DBBase
{
	var $db_name ;
	var $db_loc_db ;
	var $db_loc ;
	var $db_user ;
	var $db_pwd ;
	var $conn ;
	var $retries ;
	var $port ;
	var $link ;
	var $debug ;
	var $sleep ;
	/**
		 * Initialisation
		 *
		 * @param string $db_loc_db location along with db name ( ip:port/dbname )
		 * @param string $db_user username 
		 * @param string $db_pwd password
	*/
	function __construct( $db_loc_db = null , $db_user = null , $db_pwd = null )
	{
		( is_null( $db_loc_db ) ) ? $this->db_loc_db = DB_LOC_DB : $this->db_loc_db = $db_loc_db ;
		( is_null( $db_user ) ) ? $this->db_user = DB_USER : $this->db_user = $db_user ;
		( is_null( $db_pwd ) ) ? $this->db_pwd = DB_PWD : $this->db_pwd = $db_pwd ;
		
		list( $db_loc , $db_name ) = explode( '/' , $this->db_loc_db ) ;
		$this->db_loc = $db_loc ;
		$this->db_name = $db_name ;
		$this->debug = false ;
		$this->retries = 5 ;
		$this->sleep = 5 ;
	}
	
	/**
		 * connect a connection to the database
	*/
	function connect()
	{
		$this->link = @mysql_connect( $this->db_loc , 
									  $this->db_user , 
									  $this->db_pwd )  
					 OR die( $this->showMsg( sprintf("Failed to connect to %s ( %s : %s ) " , 
							$this->db_loc , mysql_errno() , mysql_error() )  ) ) ;

		@mysql_select_db( $this->db_name ) 
		or die( $this->showMsg( sprintf("Failed to select database %s ( %s , %s ) " , 
						  $this->db_name , mysql_errno( $this->link) , mysql_error( $this->link ) ) ) )  ;
		
		( $this->debug ) ? $this->showMsg( sprintf( "Connected to %s" , $this->db_loc) ) : '' ;
	}
	
	/**
	 * Destructor Close the connection to the database
	*/
	function __destruct()
	{
		$this->close();
	}
	
	/**
		* Close the connection to the database
	*/
	function close()
	{
		( $this->debug ) ? $this->showMsg( sprintf( "Closing the connection to the DB %s" , $this->db_loc ) )  : '' ;
		@mysql_close( $this->link ) ;
	}
	
	/**
		* Reconnect to the database 
	*/
	function reconnect()
	{
		if( !@mysql_ping( $this->link ) )
		{
			( $this->debug ) ? $this->showMsg( sprintf( "Reconnecting to the database %s , connection was reset" , $this->db_loc_db ) ) : '' ;
			$this->close();
			$this->connect();
		}
	}
	
	/**
		 * Show Debug Message
		 * @param string $msg message to show
	*/
	function showMsg( $msg )
	{
		echo "[".date( 'Y-m-d H:i:s' ). "] : ". $msg." \r\n" ;
	}
}

?>