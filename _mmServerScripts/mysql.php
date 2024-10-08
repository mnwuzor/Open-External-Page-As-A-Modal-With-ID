<?php
if (extension_loaded('mysqli')) {
// If this file is not included from the MMHTTPDB possible hacking problem.
if (!function_exists('create_error')){
	die();
}
ini_set("display_errors",1);
define('CONN_NOT_OPEN_GET_TABLES', create_error('The Connection could not be opened when trying to retrieve the tables.'));
define('CONN_NOT_OPEN_GET_DB_LIST', create_error('The Connection could not be opened when trying to retrieve the database list.'));

// Now let's handle the crashes or any other PHP errors that we can catch
function KT_ErrorHandler($errno, $errstr, $errfile, $errline) { 
	global $f, $already_sent;
	$errortype = array ( 
		1   =>  "Error", 
		2   =>  "Warning", 
		4   =>  "Parsing Error", 
		8   =>  "Notice", 
		16  =>  "Core Error", 
		32  =>  "Core Warning", 
		64  =>  "Compile Error", 
		128 =>  "Compile Warning", 
		256 =>  "User Error", 
		512 =>  "User Warning", 
		1024=>  "User Notice",
		2048=>  "E_ALL",
		2049=>  "PHP5 E_STRICT"
	
	);
	$str = sprintf("[%s]\n%s:\t%s\nFile:\t\t'%s'\nLine:\t\t%s\n\n", date('d-m-Y H:i:s'),(isset($errortype[@$errno])?$errortype[@$errno]:('Unknown '.$errno)),@$errstr,@$errfile,@$errline);
	if (error_reporting() != 0) {
			@fwrite($f, $str);
			if (@$errno == 2 && isset($already_sent) && !$already_sent==true){
				$error = '<ERRORS>'."\n";
				$error .= '<ERROR><DESCRIPTION>An Warning Type error appeared. The error is logged into the log file.</DESCRIPTION></ERROR>'."\n";
				$error .= '</ERRORS>'."\n";
				$already_sent = true;
				echo $error;
			}
	}
}
if ($debug_to_file){
		$old_error_handler = set_error_handler("KT_ErrorHandler");
}

class MySqlConnection
{
/*
 // The 'var' keyword is deprecated in PHP5 ... we will define these variables at runtime.
  var $isOpen;
	var $hostname;
	var $database;
	var $username;
	var $password;
	var $timeout;
	var $connectionId;
	var $error;
*/
	function __construct($ConnectionString, $Timeout, $Host, $DB, $UID, $Pwd)
	{
		$this->isOpen = false;
		$this->timeout = $Timeout;
		$this->error = '';

		if( $Host ) { 
			$this->hostname = $Host;
		}
		elseif( preg_match("/host=([^;]+);/", $ConnectionString, $ret) )  {
			$this->hostname = $ret[1];
		}
		
		if( $DB ) {
			$this->database = $DB;
		}
		elseif( preg_match("/db=([^;]+);/",   $ConnectionString, $ret) ) {
			$this->database = $ret[1];
		}
		
		if( $UID ) {
			$this->username = $UID;
		}
		elseif( preg_match("/uid=([^;]+);/",  $ConnectionString, $ret) ) {
			$this->username = $ret[1];
		}
		
		if( $Pwd ) {
			$this->password = $Pwd;
		}
		elseif( preg_match("/pwd=([^;]+);/",  $ConnectionString, $ret) ) {
			$this->password = $ret[1];
		}
	}

	function Open()
	{
	  $this->connectionId = new mysqli($this->hostname, $this->username, $this->password, $this->database);
		if (mysqli_connect_errno())
		{
			$this->isOpen = false;
		}
		else
		{
			$this->isOpen = true;
		}	
	}

	function TestOpen()
	{
		return ($this->isOpen) ? '<TEST status=true></TEST>' : $this->HandleException();
	}

	function Close()
	{
		if ($this->isOpen)
		{
				$this->isOpen = false;
				$this->connectionId->close();
		}
	}

	function GetTables($table_name = '')
	{
		$xmlOutput = "";
		if ($this->isOpen && isset($this->connectionId)){
			if ($table_name === ''){
					$table_name = @$_POST['Database'];
			}
			$sql = ' SHOW TABLES FROM ' . $this->ensureTicks($table_name) ;
			$results = $this->connectionId->query($sql) or $this->HandleException();

			$xmlOutput = "<RESULTSET><FIELDS>";

			$xmlOutput .= '<FIELD><NAME>TABLE_CATALOG</NAME></FIELD>';		// column 0 (zero-based)
			$xmlOutput .= '<FIELD><NAME>TABLE_SCHEMA</NAME></FIELD>';		// column 1
			$xmlOutput .= '<FIELD><NAME>TABLE_NAME</NAME></FIELD>';		// column 2

			$xmlOutput .= "</FIELDS><ROWS>";
			
			
			while ($row = $results->fetch_array()) {
					$xmlOutput .= '<ROW><VALUE/><VALUE/><VALUE>' . $row[0]. '</VALUE></ROW>';
			}
			$results->free();
}
			
			$xmlOutput .= "</ROWS></RESULTSET>";
		return $xmlOutput;
	}

	function GetViews()
	{
		// not supported
		return "<RESULTSET><FIELDS></FIELDS><ROWS></ROWS></RESULTSET>";
	}

	function GetProcedures()
	{
		// not supported
		return "<RESULTSET><FIELDS></FIELDS><ROWS></ROWS></RESULTSET>";
	}

	function GetColumnsOfTable($TableName)
	{
		$xmlOutput = "";
		//added backtick for handling reserved words and special characters
		//http://dev.mysql.com/doc/refman/5.0/en/legal-names.html
		$query  = "DESCRIBE ".$this->ensureTicks($TableName);
		$result = $this->connectionId->query($query) or $this->HandleException();

		if ($result)
		{
			$xmlOutput = "<RESULTSET><FIELDS>";

			// Columns are referenced by index, so Schema and
			// Catalog must be specified even though they are not supported
			$xmlOutput .= "<FIELD><NAME>TABLE_CATALOG</NAME></FIELD>";		// column 0 (zero-based)
			$xmlOutput .= "<FIELD><NAME>TABLE_SCHEMA</NAME></FIELD>";		// column 1
			$xmlOutput .= "<FIELD><NAME>TABLE_NAME</NAME></FIELD>";			// column 2
			$xmlOutput .= "<FIELD><NAME>COLUMN_NAME</NAME></FIELD>";
			$xmlOutput .= "<FIELD><NAME>DATA_TYPE</NAME></FIELD>";
			$xmlOutput .= "<FIELD><NAME>IS_NULLABLE</NAME></FIELD>";
			$xmlOutput .= "<FIELD><NAME>COLUMN_SIZE</NAME></FIELD>";

			$xmlOutput .= "</FIELDS><ROWS>";

			// The fields returned from DESCRIBE are: Field, Type, Null, Key, Default, Extra
			while ($row = $result->fetch_array(MYSQLI_ASSOC))
			{
				$xmlOutput .= "<ROW><VALUE/><VALUE/><VALUE/>";

				// Separate type from size. Format is: type(size)
				if (preg_match("/(.*)\((.*)\)/", $row["Type"], $ret))
				{
					$type = $ret[1];
					$size = $ret[2];
				}
				else
				{
					$type = $row["Type"];
					$size = "";
				}

				// MySQL sets nullable to "YES" or "", so we need to set "NO"
				$null = $row["Null"];
				if ($null == "")
					$null = "NO";

				$xmlOutput .= "<VALUE>" . $row["Field"] . "</VALUE>";
				$xmlOutput .= "<VALUE>" . $type         . "</VALUE>";
				$xmlOutput .= "<VALUE>" . $null         . "</VALUE>";
				$xmlOutput .= "<VALUE>" . $size         . "</VALUE></ROW>";
			}
			$result->free();

			$xmlOutput .= "</ROWS></RESULTSET>";
		}

		return $xmlOutput;
	}

	function GetParametersOfProcedure($ProcedureName, $SchemaName, $CatalogName)
	{
		// not supported on MySQL
		return '<RESULTSET><FIELDS></FIELDS><ROWS></ROWS></RESULTSET>';
	}

	function ExecuteSQL($aStatement, $MaxRows)
	{
		if ( get_magic_quotes_gpc() )
		{
				$aStatement = stripslashes( $aStatement ) ;
		}
    
    if ($aStatement == "systime") return '<RESULTSET><FIELDS><FIELD><NAME>'.time().'</NAME></FIELD></FIELDS><ROWS></ROWS></RESULTSET>';
				
		$xmlOutput = "";
		$result = $this->connectionId->query($aStatement) or $this->HandleException();
		
		if ($result)
		{
			$xmlOutput = "<RESULTSET><FIELDS>";

			$fieldCount = $result->field_count;
			for ($i=0; $i < $fieldCount; $i++)
			{
				$meta = $result->fetch_field();
				if ($meta)
				{
					$xmlOutput .= '<FIELD';
					$xmlOutput .= ' type="'			    . $meta->type;
					$xmlOutput .= '" max_length="'	. $meta->max_length;
					$xmlOutput .= '" table="'			  . $meta->table;
					$xmlOutput .= '" not_null="'		. (($meta->flags & 1)?1:0);
					$xmlOutput .= '" numeric="'		  . (($meta->flags & 32768)?1:0);
					$xmlOutput .= '" unsigned="'		. (($meta->flags & 32)?1:0);
					$xmlOutput .= '" zerofill="'		. (($meta->flags & 64)?1:0);
					$xmlOutput .= '" primary_key="'	. (($meta->flags & 2)?1:0);
					$xmlOutput .= '" multiple_key="'. (($meta->flags & 16384)?1:0);
					$xmlOutput .= '" unique_key="'	. (($meta->flags & 4)?1:0);
					$xmlOutput .= '"><NAME>'			  . $meta->name;
					$xmlOutput .= '</NAME></FIELD>';
				}
			}
			
			/*
numerics
-------------
BIT: 16
TINYINT: 1
BOOL: 1
SMALLINT: 2
MEDIUMINT: 9
INTEGER: 3
BIGINT: 8
SERIAL: 8
FLOAT: 4
DOUBLE: 5
DECIMAL: 246
NUMERIC: 246
FIXED: 246

dates
------------
DATE: 10
DATETIME: 12
TIMESTAMP: 7
TIME: 11
YEAR: 13

strings & binary
------------
CHAR: 254
VARCHAR: 253
ENUM: 254
SET: 254
BINARY: 254
VARBINARY: 253
TINYBLOB: 252
BLOB: 252
MEDIUMBLOB: 252
TINYTEXT: 252
TEXT: 252
MEDIUMTEXT: 252
LONGTEXT: 252

*/
			
/*
       NOT_NULL_FLAG = 1                                                                             
       PRI_KEY_FLAG = 2                                                                              
       UNIQUE_KEY_FLAG = 4                                                                           
       BLOB_FLAG = 16                                                                                
       UNSIGNED_FLAG = 32                                                                            
       ZEROFILL_FLAG = 64                                                                            
       BINARY_FLAG = 128                                                                             
       ENUM_FLAG = 256                                                                               
       AUTO_INCREMENT_FLAG = 512                                                                     
       TIMESTAMP_FLAG = 1024                                                                         
       SET_FLAG = 2048                                                                               
       NUM_FLAG = 32768                                                                              
       PART_KEY_FLAG = 16384                                                                         
       GROUP_FLAG = 32768                                                                            
       UNIQUE_FLAG = 65536  
*/

			$xmlOutput .= "</FIELDS><ROWS>";
			$row = $result->fetch_assoc();

			for ($i=0; $row && ($i < $MaxRows); $i++)
			{
				$xmlOutput .= "<ROW>";

				foreach ($row as $key => $value)
				{
					$xmlOutput .= "<VALUE>";
					$xmlOutput .= htmlspecialchars($value);
					$xmlOutput .= "</VALUE>";
				}

 				$xmlOutput .= "</ROW>";
				$row = $result->fetch_assoc();
			}

			$result->free();

			$xmlOutput .= "</ROWS></RESULTSET>";
		}
				
		return $xmlOutput;
	}

	function GetProviderTypes()
	{
		return '<RESULTSET><FIELDS></FIELDS><ROWS></ROWS></RESULTSET>';
	}

	function ExecuteSP($aProcStatement, $TimeOut, $Parameters)
	{
		return '<RESULTSET><FIELDS></FIELDS><ROWS></ROWS></RESULTSET>';
	}

	function ReturnsResultSet($ProcedureName)
	{
		return '<RETURNSRESULTSET status=false></RETURNSRESULTSET>';
	}

	function SupportsProcedure()
	{	
		return '<SUPPORTSPROCEDURE status=false></SUPPORTSPROCEDURE>';
	}

	/*
	*  HandleException added by InterAKT for ease in database translation answer
	*/
	function HandleException()
	{
		global $debug_to_file, $f;
		$connectionError = $this->connectionId->connect_error;
		if ($connectionError) {
			$this->error = create_error(' MySQLi Connection Error#: '. $connectionError);
		} else {
			$this->error = create_error(' MySQLi Error#: '. ((int)mysqli_errno($this->connectionId)) . "\n\n".mysqli_error($this->connectionId));
		}
		log_messages($this->error);
		die($this->error.'</HTML>');
	}

	function ensureTicks($inputSQL)
	{
		$outSQL = $inputSQL;
		//added backtick for handling reserved words and special characters
		//http://dev.mysql.com/doc/refman/5.0/en/legal-names.html

		//only add ticks if not already there
		$oLength = strlen($outSQL);
		$bHasTick = false;
		if (($oLength > 0) && (($outSQL[0] == "`") && ($outSQL[$oLength-1] == "`")))
		{
			$bHasTick = true;
		}
		if ($bHasTick == false)
		{
			$outSQL = "`".$outSQL."`";
		}
		return $outSQL;
	}

	function GetDatabaseList()
	{
		$xmlOutput = '<RESULTSET><FIELDS><FIELD><NAME>NAME</NAME></FIELD></FIELDS><ROWS>';
		if ($this->isOpen){
				$dbList = $this->connectionId->query("SHOW DATABASES") or $this->HandleException();
				$row_getRS = $dbList->fetch_assoc();

				while( $row = $dbList->fetch_row() ) {
					if (($row[0]!="information_schema") && ($row[0]!="mysql")) {
						$xmlOutput .= '<ROW><VALUE>' . $row[0] . '</VALUE></ROW>';
					}
				}
				
		}else{
				$this->error = CONN_NOT_OPEN_GET_DB_LIST;
				return $this->error;
		}
		$xmlOutput .= '</ROWS></RESULTSET>';

		return $xmlOutput;
	}

	function GetPrimaryKeysOfTable($TableName)
	{
		$xmlOutput = '';
		//added backtick for handling reserved words and special characters
		//http://dev.mysql.com/doc/refman/5.0/en/legal-names.html
		$query  = "DESCRIBE ".$this->ensureTicks($TableName);
		$result = $this->connectionId->query($query) or $this->HandleException();
		
		
		if ($result)
		{
			$xmlOutput = '<RESULTSET><FIELDS>';

			// Columns are referenced by index, so Schema and
			// Catalog must be specified even though they are not supported
			$xmlOutput .= '<FIELD><NAME>TABLE_CATALOG</NAME></FIELD>';		// column 0 (zero-based)
			$xmlOutput .= '<FIELD><NAME>TABLE_SCHEMA</NAME></FIELD>';		// column 1
			$xmlOutput .= '<FIELD><NAME>TABLE_NAME</NAME></FIELD>';			// column 2
			$xmlOutput .= '<FIELD><NAME>COLUMN_NAME</NAME></FIELD>';
			$xmlOutput .= '<FIELD><NAME>DATA_TYPE</NAME></FIELD>';
			$xmlOutput .= '<FIELD><NAME>IS_NULLABLE</NAME></FIELD>';
			$xmlOutput .= '<FIELD><NAME>COLUMN_SIZE</NAME></FIELD>';

			$xmlOutput .= '</FIELDS><ROWS>';

			// The fields returned from DESCRIBE are: Field, Type, Null, Key, Default, Extra
			while ($row = $result->fetch_array(MYSQLI_ASSOC))
			{
			  if (strtoupper($row['Key']) == 'PRI'){
  				$xmlOutput .= '<ROW><VALUE/><VALUE/><VALUE/>';
  
  				// Separate type from size. Format is: type(size)
  				if (preg_match("/(.*)\((.*)\)/", $row['Type'], $ret))
  				{
  					$type = $ret[1];
  					$size = $ret[2];
  				}
  				else
  				{
  					$type = $row['Type'];
  					$size = '';
  				}
  
  				// MySQL sets nullable to "YES" or "", so we need to set "NO"
  				$null = $row['Null'];
  				if ($null == '')
  					$null = 'NO';
  
  				$xmlOutput .= '<VALUE>' . $row['Field'] . '</VALUE>';
  				$xmlOutput .= '<VALUE>' . $type         . '</VALUE>';
  				$xmlOutput .= '<VALUE>' . $null         . '</VALUE>';
  				$xmlOutput .= '<VALUE>' . $size         . '</VALUE></ROW>';
  			}
			}
			$result->free();

			$xmlOutput .= '</ROWS></RESULTSET>';
		}
		return $xmlOutput;
	}

}	// class MySqlConnection
} else {
// If this file is not included from the MMHTTPDB possible hacking problem.
if (!function_exists('create_error')){
	die();
}

define('MYSQL_NOT_EXISTS', create_error("Your PHP server doesn't have the MySQL or MySQLi module loaded or you can't use the mysql_(p)connect functions."));
define('CONN_NOT_OPEN_GET_TABLES', create_error('The Connection could not be opened when trying to retrieve the tables.'));
define('CONN_NOT_OPEN_GET_DB_LIST', create_error('The Connection could not be opened when trying to retrieve the database list.'));
			 
if (!function_exists('mysql_connect') || !function_exists('mysql_pconnect') || !extension_loaded('mysql')){
	echo MYSQL_NOT_EXISTS;
	die();
}

// Now let's handle the crashes or any other PHP errors that we can catch
function KT_ErrorHandler($errno, $errstr, $errfile, $errline) { 
	global $f, $already_sent;
	$errortype = array ( 
		1   =>  "Error", 
		2   =>  "Warning", 
		4   =>  "Parsing Error", 
		8   =>  "Notice", 
		16  =>  "Core Error", 
		32  =>  "Core Warning", 
		64  =>  "Compile Error", 
		128 =>  "Compile Warning", 
		256 =>  "User Error", 
		512 =>  "User Warning", 
		1024=>  "User Notice",
		2048=>  "E_ALL",
		2049=>  "PHP5 E_STRICT"
	
	);
	$str = sprintf("[%s]\n%s:\t%s\nFile:\t\t'%s'\nLine:\t\t%s\n\n", date('d-m-Y H:i:s'),(isset($errortype[@$errno])?$errortype[@$errno]:('Unknown '.$errno)),@$errstr,@$errfile,@$errline);
	if (error_reporting() != 0) {
			@fwrite($f, $str);
			if (@$errno == 2 && isset($already_sent) && !$already_sent==true){
				$error = '<ERRORS>'."\n";
				$error .= '<ERROR><DESCRIPTION>An Warning Type error appeared. The error is logged into the log file.</DESCRIPTION></ERROR>'."\n";
				$error .= '</ERRORS>'."\n";
				$already_sent = true;
				echo $error;
			}
	}
}
if ($debug_to_file){
		$old_error_handler = set_error_handler("KT_ErrorHandler");
}

class MySqlConnection
{
/*
 // The 'var' keyword is deprecated in PHP5 ... we will define these variables at runtime.
  var $isOpen;
	var $hostname;
	var $database;
	var $username;
	var $password;
	var $timeout;
	var $connectionId;
	var $error;
*/
	function __construct($ConnectionString, $Timeout, $Host, $DB, $UID, $Pwd)
	{
		$this->isOpen = false;
		$this->timeout = $Timeout;
		$this->error = '';

		if( $Host ) { 
			$this->hostname = $Host;
		}
		elseif( preg_match("/host=([^;]+);/", $ConnectionString, $ret) )  {
			$this->hostname = $ret[1];
		}
		
		if( $DB ) {
			$this->database = $DB;
		}
		elseif( preg_match("/db=([^;]+);/",   $ConnectionString, $ret) ) {
			$this->database = $ret[1];
		}
		
		if( $UID ) {
			$this->username = $UID;
		}
		elseif( preg_match("/uid=([^;]+);/",  $ConnectionString, $ret) ) {
			$this->username = $ret[1];
		}
		
		if( $Pwd ) {
			$this->password = $Pwd;
		}
		elseif( preg_match("/pwd=([^;]+);/",  $ConnectionString, $ret) ) {
			$this->password = $ret[1];
		}
	}

	function Open()
	{
	  $this->connectionId = mysql_connect($this->hostname, $this->username, $this->password);
		if (isset($this->connectionId) && $this->connectionId && is_resource($this->connectionId))
		{
			$this->isOpen = ($this->database == "") ? true : mysql_select_db($this->database, $this->connectionId);
		}
		else
		{
			$this->isOpen = false;
		}	
	}

	function TestOpen()
	{
		return ($this->isOpen) ? '<TEST status=true></TEST>' : $this->HandleException();
	}

	function Close()
	{
		if (is_resource($this->connectionId) && $this->isOpen)
		{
			if (mysql_close($this->connectionId))
			{
				$this->isOpen = false;
				unset($this->connectionId);
			}
		}
	}

	function GetTables($table_name = '')
	{
		$xmlOutput = "";
		if ($this->isOpen && isset($this->connectionId) && is_resource($this->connectionId)){
			// 1. mysql_list_tables and mysql_tablename are deprecated in PHP5
			// 2. For backward compatibility GetTables don't have any parameters
			if ($table_name === ''){
					$table_name = @$_POST['Database'];
			}
			//added backtick for handling reserved words and special characters
			//http://dev.mysql.com/doc/refman/5.0/en/legal-names.html
			$sql = ' SHOW TABLES FROM ' . $this->ensureTicks($table_name) ;
			$results = mysql_query($sql, $this->connectionId) or $this->HandleException();

			$xmlOutput = "<RESULTSET><FIELDS>";

			// Columns are referenced by index, so Schema and
			// Catalog must be specified even though they are not supported

			$xmlOutput .= '<FIELD><NAME>TABLE_CATALOG</NAME></FIELD>';		// column 0 (zero-based)
			$xmlOutput .= '<FIELD><NAME>TABLE_SCHEMA</NAME></FIELD>';		// column 1
			$xmlOutput .= '<FIELD><NAME>TABLE_NAME</NAME></FIELD>';		// column 2

			$xmlOutput .= "</FIELDS><ROWS>";

			if (is_resource($results) && mysql_num_rows($results) > 0){
					while ($row = mysql_fetch_array($results)){
							$xmlOutput .= '<ROW><VALUE/><VALUE/><VALUE>' . $row[0]. '</VALUE></ROW>';	
					}
			}
			$xmlOutput .= "</ROWS></RESULTSET>";

    }
		return $xmlOutput;
	}

	function GetViews()
	{
		// not supported
		return "<RESULTSET><FIELDS></FIELDS><ROWS></ROWS></RESULTSET>";
	}

	function GetProcedures()
	{
		// not supported
		return "<RESULTSET><FIELDS></FIELDS><ROWS></ROWS></RESULTSET>";
	}

	function GetColumnsOfTable($TableName)
	{
		$xmlOutput = "";
		//added backtick for handling reserved words and special characters
		//http://dev.mysql.com/doc/refman/5.0/en/legal-names.html
		$query  = "DESCRIBE ".$this->ensureTicks($TableName);
		$result = mysql_query($query) or $this->HandleException();

		if ($result)
		{
			$xmlOutput = "<RESULTSET><FIELDS>";

			// Columns are referenced by index, so Schema and
			// Catalog must be specified even though they are not supported
			$xmlOutput .= "<FIELD><NAME>TABLE_CATALOG</NAME></FIELD>";		// column 0 (zero-based)
			$xmlOutput .= "<FIELD><NAME>TABLE_SCHEMA</NAME></FIELD>";		// column 1
			$xmlOutput .= "<FIELD><NAME>TABLE_NAME</NAME></FIELD>";			// column 2
			$xmlOutput .= "<FIELD><NAME>COLUMN_NAME</NAME></FIELD>";
			$xmlOutput .= "<FIELD><NAME>DATA_TYPE</NAME></FIELD>";
			$xmlOutput .= "<FIELD><NAME>IS_NULLABLE</NAME></FIELD>";
			$xmlOutput .= "<FIELD><NAME>COLUMN_SIZE</NAME></FIELD>";

			$xmlOutput .= "</FIELDS><ROWS>";

			// The fields returned from DESCRIBE are: Field, Type, Null, Key, Default, Extra
			while ($row = mysql_fetch_array($result, MYSQL_ASSOC))
			{
				$xmlOutput .= "<ROW><VALUE/><VALUE/><VALUE/>";

				// Separate type from size. Format is: type(size)
				if (preg_match("/(.*)\((.*)\)/", $row["Type"], $ret))
				{
					$type = $ret[1];
					$size = $ret[2];
				}
				else
				{
					$type = $row["Type"];
					$size = "";
				}

				// MySQL sets nullable to "YES" or "", so we need to set "NO"
				$null = $row["Null"];
				if ($null == "")
					$null = "NO";

				$xmlOutput .= "<VALUE>" . $row["Field"] . "</VALUE>";
				$xmlOutput .= "<VALUE>" . $type         . "</VALUE>";
				$xmlOutput .= "<VALUE>" . $null         . "</VALUE>";
				$xmlOutput .= "<VALUE>" . $size         . "</VALUE></ROW>";
			}
			mysql_free_result($result);

			$xmlOutput .= "</ROWS></RESULTSET>";
		}

		return $xmlOutput;
	}

	function GetParametersOfProcedure($ProcedureName, $SchemaName, $CatalogName)
	{
		// not supported on MySQL
		return '<RESULTSET><FIELDS></FIELDS><ROWS></ROWS></RESULTSET>';
	}

	function ExecuteSQL($aStatement, $MaxRows)
	{
		if ( get_magic_quotes_gpc() )
		{
				$aStatement = stripslashes( $aStatement ) ;
		}
    
    if ($aStatement == "systime") return '<RESULTSET><FIELDS><FIELD><NAME>'.time().'</NAME></FIELD></FIELDS><ROWS></ROWS></RESULTSET>';
				
		$xmlOutput = "";

		$result = mysql_query($aStatement) or $this->HandleException();
		
		if (isset($result) && is_resource($result))
		{
			$xmlOutput = "<RESULTSET><FIELDS>";

			$fieldCount = mysql_num_fields($result);
			for ($i=0; $i < $fieldCount; $i++)
			{
				$meta = mysql_fetch_field($result);
				if ($meta)
				{
					$xmlOutput .= '<FIELD';
					$xmlOutput .= ' type="'			    . $meta->type;
					$xmlOutput .= '" max_length="'	. $meta->max_length;
					$xmlOutput .= '" table="'			  . $meta->table;
					$xmlOutput .= '" not_null="'		. $meta->not_null;
					$xmlOutput .= '" numeric="'		  . $meta->numeric;
					$xmlOutput .= '" unsigned="'		. $meta->unsigned;
					$xmlOutput .= '" zerofill="'		. $meta->zerofill;
					$xmlOutput .= '" primary_key="'	. $meta->primary_key;
					$xmlOutput .= '" multiple_key="'. $meta->multiple_key;
					$xmlOutput .= '" unique_key="'	. $meta->unique_key;
					$xmlOutput .= '"><NAME>'			  . $meta->name;
					$xmlOutput .= '</NAME></FIELD>';
				}
			}

			$xmlOutput .= "</FIELDS><ROWS>";
			$row = mysql_fetch_assoc($result);

			for ($i=0; $row && ($i < $MaxRows); $i++)
			{
				$xmlOutput .= "<ROW>";

				foreach ($row as $key => $value)
				{
					$xmlOutput .= "<VALUE>";
					$xmlOutput .= htmlspecialchars($value);
					$xmlOutput .= "</VALUE>";
				}

 				$xmlOutput .= "</ROW>";
				$row = mysql_fetch_assoc($result);
			}

			mysql_free_result($result);

			$xmlOutput .= "</ROWS></RESULTSET>";
		}
				
		return $xmlOutput;
	}

	function GetProviderTypes()
	{
		return '<RESULTSET><FIELDS></FIELDS><ROWS></ROWS></RESULTSET>';
	}

	function ExecuteSP($aProcStatement, $TimeOut, $Parameters)
	{
		return '<RESULTSET><FIELDS></FIELDS><ROWS></ROWS></RESULTSET>';
	}

	function ReturnsResultSet($ProcedureName)
	{
		return '<RETURNSRESULTSET status=false></RETURNSRESULTSET>';
	}

	function SupportsProcedure()
	{	
		return '<SUPPORTSPROCEDURE status=false></SUPPORTSPROCEDURE>';
	}

	/*
	*  HandleException added by InterAKT for ease in database translation answer
	*/
	function HandleException()
	{
		global $debug_to_file, $f;
		$this->error = create_error(' MySQL Error#: '. ((int)mysql_errno()) . "\n\n".mysql_error());
		log_messages($this->error);
		die($this->error.'</HTML>');
	}

	function ensureTicks($inputSQL)
	{
		$outSQL = $inputSQL;
		//added backtick for handling reserved words and special characters
		//http://dev.mysql.com/doc/refman/5.0/en/legal-names.html

		//only add ticks if not already there
		$oLength = strlen($outSQL);
		$bHasTick = false;
		if (($oLength > 0) && (($outSQL[0] == "`") && ($outSQL[$oLength-1] == "`")))
		{
			$bHasTick = true;
		}
		if ($bHasTick == false)
		{
			$outSQL = "`".$outSQL."`";
		}
		return $outSQL;
	}

	function GetDatabaseList()
	{
		$xmlOutput = '<RESULTSET><FIELDS><FIELD><NAME>NAME</NAME></FIELD></FIELDS><ROWS>';

		if (isset($this->connectionId) && is_resource($this->connectionId)){
				$dbList = mysql_list_dbs($this->connectionId);
				
				while ($row = mysql_fetch_object($dbList))
				{
					$xmlOutput .= '<ROW><VALUE>' . $row->Database . '</VALUE></ROW>';
				}
    }else{
				$this->error = CONN_NOT_OPEN_GET_DB_LIST;
				return $this->error;
		}
		$xmlOutput .= '</ROWS></RESULTSET>';

		return $xmlOutput;
	}

	function GetPrimaryKeysOfTable($TableName)
	{
		$xmlOutput = '';
		//added backtick for handling reserved words and special characters
		//http://dev.mysql.com/doc/refman/5.0/en/legal-names.html
		$query  = "DESCRIBE ".$this->ensureTicks($TableName);
		$result = mysql_query($query) or $this->HandleException();
		
		
		if ($result)
		{
			$xmlOutput = '<RESULTSET><FIELDS>';

			// Columns are referenced by index, so Schema and
			// Catalog must be specified even though they are not supported
			$xmlOutput .= '<FIELD><NAME>TABLE_CATALOG</NAME></FIELD>';		// column 0 (zero-based)
			$xmlOutput .= '<FIELD><NAME>TABLE_SCHEMA</NAME></FIELD>';		// column 1
			$xmlOutput .= '<FIELD><NAME>TABLE_NAME</NAME></FIELD>';			// column 2
			$xmlOutput .= '<FIELD><NAME>COLUMN_NAME</NAME></FIELD>';
			$xmlOutput .= '<FIELD><NAME>DATA_TYPE</NAME></FIELD>';
			$xmlOutput .= '<FIELD><NAME>IS_NULLABLE</NAME></FIELD>';
			$xmlOutput .= '<FIELD><NAME>COLUMN_SIZE</NAME></FIELD>';

			$xmlOutput .= '</FIELDS><ROWS>';

			// The fields returned from DESCRIBE are: Field, Type, Null, Key, Default, Extra
			while ($row = mysql_fetch_array($result, MYSQL_ASSOC))
			{
			  if (strtoupper($row['Key']) == 'PRI'){
  				$xmlOutput .= '<ROW><VALUE/><VALUE/><VALUE/>';
  
  				// Separate type from size. Format is: type(size)
  				if (preg_match("/(.*)\((.*)\)/", $row['Type'], $ret))
  				{
  					$type = $ret[1];
  					$size = $ret[2];
  				}
  				else
  				{
  					$type = $row['Type'];
  					$size = '';
  				}
  
  				// MySQL sets nullable to "YES" or "", so we need to set "NO"
  				$null = $row['Null'];
  				if ($null == '')
  					$null = 'NO';
  
  				$xmlOutput .= '<VALUE>' . $row['Field'] . '</VALUE>';
  				$xmlOutput .= '<VALUE>' . $type         . '</VALUE>';
  				$xmlOutput .= '<VALUE>' . $null         . '</VALUE>';
  				$xmlOutput .= '<VALUE>' . $size         . '</VALUE></ROW>';
  			}
			}
			mysql_free_result($result);

			$xmlOutput .= '</ROWS></RESULTSET>';
		}
		return $xmlOutput;
	}

}	// class MySqlConnection

}
?>