<?php


class Configuration
{  
	public $config; // 	hash, uid, expiredate, ip, fn, ln, type
	
	function __construct()
	{
		include("config.php");
	
		$this->mysqli = new mysqli($db_config['host'], $db_config['user'], $db_config['pass'], $db_config['name']); 

		$this->errormsg = array();
		$this->successmsg = array();

		$this->init();

		//echo $this->mysqli === false ? "Error connecting to DB" : "DB OK";
	}

    
	
	/*
	* Init 
	*
	*/
	
	public function init()
	{
        global $configTable;

        $query = $this->mysqli->prepare("	SELECT *
                                            
                                            FROM $configTable");

       
        $query->execute();
       
        $res = $query->get_result();
		$this->config = array();
        
		while ($row = $res -> fetch_assoc()) {
			$this->config[$row['name']] = $row['value'];
		}

        $query->close();


    }

	public function read_config($name){
		return isset($this->config[$name]) ? $this->config[$name] : null;
	}

	public function update_config($name, $value){
		global $configTable;

        $query = $this->mysqli->prepare("	INSERT INTO $configTable (name, value)
											VALUES ('$name', '$value')
											ON DUPLICATE KEY UPDATE value = '$value'");

       
        $query->execute();               
        $query->close();
		
		$this->init();
	}

}


?>