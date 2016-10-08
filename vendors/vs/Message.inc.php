<?php
include_once( 'db/db.config.php') ;
include_once( 'db/mysql.inc.php') ;

class Message
{
	var $mysql ;
	var $size ;
	var $container ;
	var $id ;
	var $debug ;
	var $msg ;
	var $table ;
	
	/**
	 * Construct the Message class
	 *
	 * @param int $id the id of the message
	 * @param Mysql $mysql resourc mysql
	 * @param boolean $debug
	 */
	function __construct( $id , $mysql , $debug = false ) 
	{
		$this->mysql = $mysql ;
		$this->id = $id ;
		$this->debug = $debug ;
		$this->table = "gamma_messages" ;
				
		if( !is_resource( $mysql ) )
		{
			$mysql = new Mysql( $mysql );
			$mysql->debug = $this->debug ;
			$mysql->connect();
			$this->mysql = $mysql ;
		}
		
		$this->msg = $this->get() ;
	}
	
	/**
	 * Get the message from the database
	 *
	 * @return string
	 */function get()
	{
		$sql = "SELECT message FROM ".$this->table." WHERE id = ".$this->id ;
		list( $rs ) = $this->mysql->oneQuery( $sql ) ;
		$this->mysql->close();
		return $rs ;
	}
	
	/**
	 * Getting the message that is parse
	 *
	 * @return string
	 */
	function getMessage()
	{
		return $this->getParseMessage( $this->msg ) ;	
	}
	
	/**
	 * Subject to use
	 *
	 * @return string
	 */
	function getSubject()
	{
		//Subject to send if there is subject
		$subj = "[hey|hi][,|..] [how's it going|what's up][!|?]";
	
		//We need to parse the subject
		$subj = getParseMessage($subj) ;
		return $subj;
	}
	
	/**
	 * Parsing a string
	 *
	 * @param string $str string to pass to the function
	 * @return string
	 */
	function getParseMessage( $str )
	{
		$newmsg = "";
		$parsemult = "" ;
		
		for ($s = 0; $s < strlen( $this->msg ) ; $s++)
		{
			if ($parsemult == 1) {
				$parsemsg .= substr( $this->msg, $s, 1);
			}
	
			if (substr( $this->msg, $s, 1) == "[") {
				$parsemsg = "";
				$parsemult = 1;
			}
	
			if ($parsemult == 0) {
				$newmsg .= substr( $this->msg, $s, 1);
			}
	
			if (substr( $this->msg, $s, 1) == "]") {
				$parsemult = 0;
				$parsemsg = substr($parsemsg, 0, -1);
				$choices = split("\|", $parsemsg);
				$numchoices = count($choices)-1;
				$choice = rand(0, $numchoices);
				$newmsg .= $choices[$choice];
			}
		}
	    return $newmsg;
	}
	
	/**
	 * Replace the some keywords on the message
	 *
	 * @param array $replace array of fields and values to replace
	 * @return string
	 */
	function getReplaceMsg( $replace )
	{
		$msg = $this->getMessage() ;
		foreach ($replace as $key => $value)
		{
			$msg = str_replace($key , $value , $msg) ;
		}
		return $msg ;
	}
}
