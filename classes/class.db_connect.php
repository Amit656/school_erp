<?php
require_once('class.app_errors.php');

// THE DATABASE CLASSES ARE DIVIDED INTO 2 CLASSES AND USE THE ADAPTER PATTERN//
// THE FIRST DBConnect CLASS IS USED TO GET THE CONNECTION OBJECT//
class DBConnect
{
	protected $HostName;
	protected $UserName;
	protected $Password;
	protected $DBName;
	
	protected $MySQLConn;  // Database connection handle
	
	public function __construct($Mode = 0) 
	{
		if ($Mode == 0)
		{
			$this->HostName = 'localhost';
    		$this->UserName = 'root';
    		$this->Password = '';
    		$this->DBName = 'lucknowips_16Apr2020';
		}
		else
		{
			$this->HostName = 'localhost';
			$this->UserName = 'addedsch_demo';
			$this->Password = 'Zt14d;V^o,Pc';
			$this->DBName = 'addedsch_central';
		}

		if(!$this->MySQLConn)
		{
			$this->Connect();
		}
	}
	
	public function __destruct()
	{
		if (isset($MySQLConn))
		{
			unset($MySQLConn);
		}
	}
	
	public function BeginTransaction()
	{
		$this->MySQLConn->autocommit(FALSE);
	}
	
	public function RollBackTransaction()
	{
		$this->MySQLConn->rollback();
	}
	
	public function CommitTransaction()
	{
		$this->MySQLConn->commit();
	}
	
	public function RealEscapeVariable($QueryVariable)
	{
		return "'".$this->MySQLConn->real_escape_string($QueryVariable)."'";
	}
	
	// THIS METHOD IS USED TO CONNECT TO THE DATABASE //
	protected function Connect()
	{
		// THE ERROR SUPPRESSION OPERATOR '@' IS USED TO SUPPRESS PHP WARNING IF THE CONNECTION CANNOT BE MADE //
		$this->MySQLConn = @new mysqli($this->HostName, $this->UserName, $this->Password);
		
		// CHECK IF THE CONNEXTION OBJECT IS RETURNED //
		if(!is_object($this->MySQLConn))
		{
			throw new ApplicationDBException('', APP_DB_ERROR_NO_CONNECTION);
		}
		// SELECT THE DATABASE TO BE USED //
		// THE ERROR SUPPRESSION OPERATOR '@' IS USED TO SUPPRESS PHP WARNING IF THE DATABASE CANNOT BE SELECTED //		
		
		if(!@$this->MySQLConn->select_db($this->DBName)) 
		{
			throw new ApplicationDBException('', APP_DB_ERROR_NO_DATABASE);
		}
	}
	
	// THIS METHOD PREPARES THE QUERY AND GETS THE STATEMENT OBJECT
	public function Prepare($Query)
	{
		return new DBConnect_Statement($this->MySQLConn, $Query);
	} 
}

//**********************************************************************************//

// THE SECOND DATABASE CLASS DBConnect_Statement IS USED TO GET THE RESULT SETS//
class DBConnect_Statement 
{
	protected $MySQLConn;
	public $Result;
	public $Query;
	public $BindVariables;
	public $LastID;
	public $TotalRowsReturned;
	public $Transaction;
	
	public function __construct($MySQLConn, $Query)
	{
		$this->Query = $Query;
		$this->LastID = 0;
		$this->TotalRowsReturned = 0;
		$this->Transaction = false;
		$this->MySQLConn = $MySQLConn;
		if(!is_object($MySQLConn)) 
		{
			throw new ApplicationDBException('', APP_DB_ERROR_NO_CONNECTION);
		}
	}
	
	// THIS METHOD IS USED TO GET THE RECORDSET OBJECT FROM THE STATEMENT OBJECT//
	public function FetchRow()
	{
		// CHECK IF THE QUERY WAS EXECUTED //
		if(!$this->Result) 
		{
			throw new ApplicationDBException('', APP_DB_ERROR_QUERY_FAILED);
		}
		// CHECK IF NO ROWS WERE RETURNED //
		if ($this->Result->num_rows > 0)
		{
			return $this->Result->fetch_object();
		}
		else
		{
			throw new ApplicationDBException('', APP_DB_ERROR_NO_RECORDS);
		}
	}
	
	// THIS METHOD WILL TAKE THE VARIABLES OF THE QUERIES, ESCAPE THEM AND PUT QUOTES(') AROUND THEM //
	public function Execute() 
	{
		//GET THE QUERY VARIABLES IN AN ARRAY AND CREATE ANOTHER ARRAY FROM IT WITH THE INDEX STARTING FROM 1 //
		$BindVariablesReturned = func_get_args();
		foreach($BindVariablesReturned as $Index => $Value) 
		{
			$this->BindVariables[$Index + 1] = $Value;
		}
		
		// RECREATE QUERY AFTER ESCAPING AND QUOTING IT //
		$Query = $this->Query;
		if (isset($this->BindVariables))
		{
			// NOW REPLACE EACH VARIABLE PLACE HOLDER IN THE QUERY WITH THE ACTUAL ESCAPED AND QUOTED VARIABLE //
			foreach ($this->BindVariables as $PlaceHolder => $QueryVariable)
			{
				$PlaceHolder = ":|".$PlaceHolder;

				$Query = substr_replace($Query, "'".$this->MySQLConn->real_escape_string($QueryVariable)."'", strpos($Query, $PlaceHolder), strlen($PlaceHolder));
			}
		}
				
		// NOW EXECUTE THE QUERY //
		$this->Result = $this->MySQLConn->query($Query);
		
 		//echo ('$Query='.$Query.'<br>');
        // error_log ('$Query='.$Query.'<br>');

        if (isset($_GET['print_query']))
        {
            echo ('$Query='.$Query.'<br>');
        }
		
		// CHECK IF THE QUERY WAS EXECUTED // 
		if(!$this->Result) 
		{
			throw new ApplicationDBException('', APP_DB_ERROR_QUERY_FAILED);
		}
		
		// GET THE LAST AUTOINCREMENT VALUE //		
		$this->LastID = $this->MySQLConn->insert_id;
		
		// GET THE TOTAL NUMBER OF ROWS RETURNED //
		//$this->TotalRowsReturned = $this->Result->num_rows;
		
		// RETURN THE RESULT TO THE CALLING STATEMENT //
		return $this->Result;
	}		
	
	public function GetRecordsEffected()
	{
		return $this->MySQLConn->affected_rows;
	}
}
?>