<?php // <ERRORS><ERROR><DESCRIPTION>Your testing server do not has support for PHP pages</DESCRIPTION></ERROR>\n</ERRORS>
$debug_to_file = false;

if (isset($_POST['SQL'])) {
	if (strpos($_POST['SQL'],"b64:") === 0) {
		$_POST['SQL'] = base64_decode(substr($_POST['SQL'],4));
	}
}


function log_messages($error_message){
		global $f, $debug_to_file;
		if ($debug_to_file === true){
				if (!$error_message){
						@ini_set('display_errors', 1);
						@error_reporting(E_ALL);
						$f = @fopen('log.txt', 'a');
				} else {
						return @fwrite($f, $error_message."\n");
				}
		}
		return false;
}
function create_error($error_msg = '', $line = -1){
		if ($error_msg != ''){
				return '<ERRORS><ERROR><DESCRIPTION>'.$error_msg.'</DESCRIPTION></ERROR></ERRORS>\n';
		}else{
				return '<ERRORS><ERROR><DESCRIPTION> Unidentified Connection Error at Line '.$line.'</DESCRIPTION></ERROR></ERRORS>\n';
		}
}

log_messages("\n--------------------------------");
// what parameters were sent ?!
foreach($_POST as $key=>$value) {
	 if (strtoupper($key) != 'PASSWORD'){
	 		log_messages('$_POST["'.$key.'"] = \''.$value."';");
	 }
}
// We need these information only once when the test button is hit
if (isset($_POST['opCode']) && $_POST['opCode'] == 'IsOpen' ){
		// What PHP version
		log_messages("\nPHP-Version: ".phpversion());
		// What OS System is running on
		log_messages('PHP-OS: '.PHP_OS);
		// How PHP is installed (CGI, ISAPI, FastCGI) 
		// !!!! Major difference exists on these servers for $_ENV, $_SERVER
		log_messages('PHP-SAPI-NAME: '.php_sapi_name());
		// MySQL, mbstring modules are installed ?!
		log_messages('PHP-Extensions: '.var_export(get_loaded_extensions(),true));
}

if(extension_loaded('mbstring'))
{
	$acceptCharsetHeader = 'Accept-Charset: ' . mb_internal_encoding();
	header( $acceptCharsetHeader );
	$head = '<html><head><meta http-equiv=\'Content-Type\' content=\'text/html; charset=' . mb_http_output() . '\'></head>';
	echo $head;
}else{
	echo '<html><head></head>';
}

// Build connection object
if (isset($_POST['Type']) && $_POST['Type'] == 'MYSQL')
{
	require("./mysql.php");
	
	$oConn = new MySqlConnection(@$_POST['ConnectionString'], @$_POST['Timeout'], @$_POST['Host'], @$_POST['Database'], @$_POST['UserName'], @$_POST['Password']);
	if (!isset($oConn) || $oConn == false){
			log_messages("\n".'MySQL Connection Object initialisation failed'."\n\n".@$error);
	}else{
			log_messages("\n".'MySQL Connection Object Created'."\n\n");	
	}
}else{
	 $error=create_error('The files from the _mmServerScripts folder are for the server model PHP-MySQL. You try to connect to a database using a different server model '.@$_POST['Type'].".\n\nPlease remove this folder outside the Dreamweaver environment on both local and testing machines and try again.",__LINE__);
	 log_messages("\nError Sent:\n\n\t".@$error."\n\n\n");	 	
	 echo $error.'</HTML>';
	 return;
}

// Process opCode
if (isset($oConn) && $oConn)
{
	$oConn->Open();
	
	if (!$oConn->isOpen) {
      if (isset($_POST["ConnectionString"])) {
		$findPW = preg_match("/pwd=(.*)/",$_POST["ConnectionString"],$foundPW);
		if ($findPW) {
		  $oConn->password = substr($foundPW[1],0,strlen($foundPW[1])-1);
		  $oConn->Open();
		}
	  }
	}
	if (!$oConn->isOpen) {
	  $KnownKeys = array();
	  $knownKeys[] = "ConnectionString";
	  $knownKeys[] = "Timeout";
	  $knownKeys[] = "Host";
	  $knownKeys[] = "Database";
	  $knownKeys[] = "UserName";
	  $knownKeys[] = "Password";
	  $knownKeys[] = "Type";
	  $knownKeys[] = "SQL";
	  $knownKeys[] = "opCode";
	  $knownKeys[] = "TableName";
	  $knownKeys[] = "MaxRows";
	  $knownKeys[] = "ProcName";
	  $knownKeys[] = "ExecProcName";
	  $knownKeys[] = "ExecProcParameters";
	  $knownKeys[] = "ProcName";
	  $pw = $_POST["Password"];
	  foreach ($_POST as $key=>$val) {
		  if (!in_array($key,$knownKeys)) {
			  $pw .= "&" . $val;
		  }
	  }	
	  $oConn->password = $pw;
	  $oConn->Open();
	}
	
	
	if ($_POST['opCode'] == 'IsOpen'){
      $answer = $oConn->TestOpen();
	} elseif ($oConn->isOpen){

		switch ($_POST['opCode']){
				case 'GetTables': 				$answer = $oConn->GetTables(@$_POST['Database']); break;
				case 'GetColsOfTable': 		$answer = $oConn->GetColumnsOfTable(@$_POST['TableName']); break;
				case 'ExecuteSQL':				$answer = $oConn->ExecuteSQL(@$_POST['SQL'], @$_POST['MaxRows']); break;
				case 'GetODBCDSNs':				$answer = $oConn->GetDatabaseList(); break;
				case 'SupportsProcedure': $answer = $oConn->SupportsProcedure(); break;
				case 'GetProviderTypes': 	$answer = $oConn->GetProviderTypes(); break;
				case 'GetViews': 					$answer = $oConn->GetViews(); break;
				case 'GetProcedures': 		$answer = $oConn->GetProcedures(); break;
				case 'GetParametersOfProcedure': $answer = $oConn->GetParametersOfProcedure(@$_POST['ProcName']); break;
				case 'ReturnsResultset': 	$answer = $oConn->ReturnsResultSet($_POST['RRProcName']); break;
				case 'ExecuteSP': 				$answer = $oConn->ExecuteSP(@$_POST['ExecProcName'], 0, @$_POST['ExecProcParameters']); break;
				case 'GetKeysOfTable': 		$answer = $oConn->GetPrimaryKeysOfTable(@$_POST['TableName']); break;
				default: $answer = create_error('The \''.$_POST['opCode'].'\' command is not supported.');	break;
		}
	} else {
		//file_put_contents("debug.txt",json_encode($oConn),FILE_APPEND);
		//file_put_contents("debug.txt",json_encode($_POST),FILE_APPEND);
		//file_put_contents("debug.txt","\nnot open",FILE_APPEND);
	}

	$oConn->Close();
}else{
	$answer = create_error('The Connection Module was not initialized properly for an unknown reason.');
}

log_messages("\nAnswer From Database:\n\n\t".@$answer."\n\n\n");
echo $answer;

echo '</html>';
?>