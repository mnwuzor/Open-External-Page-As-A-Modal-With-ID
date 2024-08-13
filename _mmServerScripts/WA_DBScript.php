<?php
// version 1.0
if((function_exists("get_magic_quotes_gpc") && get_magic_quotes_gpc()) || (ini_get('magic_quotes_sybase') && ( strtolower(ini_get('magic_quotes_sybase')) != "off" ))) {
            foreach($_POST as $k => $v)  {
				if (is_array($_POST[$k]))  {
             		foreach($_POST[$k] as $x => $y)  {
				  		$_POST[$k][$x] = stripslashes($y);
					}
				} else {
				  $_POST[$k] = stripslashes($v);
				}
			}
}
foreach($_POST as $k => $v)  {
	if (is_array($_POST[$k]))  {
        foreach($_POST[$k] as $x => $y)  {
			$_POST[$k][$x] = urldecode(base64_decode(stripslashes($y)));
		}
	} else {
		$_POST[$k] = urldecode(base64_decode(stripslashes($v)));
	}
}

if (!isset($_POST["od_in"]) || intval($_POST["od_in"]) != intval("@@RANDNUM@@")) die("<WADB_RESPONSE><WADB_ERROR code=\"1\"><![CDATA[No hacking, please. Your location has been logged and reported to the FBI. Please wait for the authorities to arrive.]]></WADB_ERROR></WADB_RESPONSE>");
if (!isset($_POST["ch_ew_y"]) || intval($_POST["ch_ew_y"]) != intval("@@TIMESTAMP@@")) die("<WADB_RESPONSE><WADB_ERROR code=\"2\"><![CDATA[No hacking, please. Your location has been logged and reported to the FBI. Please wait for the authorities to arrive.]]></WADB_ERROR></WADB_RESPONSE>");
$timestampExpires = intval("@@TSEXPIRES@@") / 1000;
$presTime = time(intval("@@TIMESTAMP@@") / 1000);
$servTime = intval("@@SERVTIMESTAMP@@");
if ($servTime && (time() - $timestampExpires) >= $servTime) die("<WADB_RESPONSE><WADB_ERROR code=\"3\"><![CDATA[No hacking, please. Your location has been logged and reported to the FBI. Please wait for the authorities to arrive.]]></WADB_ERROR></WADB_RESPONSE>");


$connectionText = $_POST['connection'];
$host = $_POST['hostname']; 
$user = $_POST['username']; 
$pass = $_POST['password']; 
$database = $_POST['databasename']; 

if (strlen($pass) == 1 && ord($_POST['password']) == 0) {
  $pass = NULL;
} 

if (!extension_loaded('mysqli')) {
  $connection = mysql_connect($host, $user, $pass, $database);
  mysql_select_db($database,$connection);
} else {
  $connection = new mysqli($host, $user, $pass, $database);
}
//die($host ."\n". $user."\n". $pass."\n". $database);

if ($connection->connect_errno) {
	die("<WADB_RESPONSE><WADB_ERROR code=\"10\"><![CDATA[Failed to connect to MySQL: (" . $connection->connect_errno . ") " . $connection->connect_error ."]]></WADB_ERROR></WADB_RESPONSE>");
}

$_POST['action'] = ( (isset($_POST['action'])) ? $_POST['action'] : 'query' );
$columnString = "";
$valuesString = "";
$conditionString = "";
switch ($_POST['action']) {
	case "insert":
		if (isset($_POST['values']))  {
			$valuesString = $_POST['values'];
			if (is_array($valuesString))  {
				$valuesString = implode(", ", $valuesString);
			}
		}
		if (isset($_POST['conditions']))  {
			$conditionString = $_POST['conditions'];
			if (is_array($conditionString))  {
				$conditionString = implode(" AND ", $conditionString);
			}
		}
		//no break for columns string as the "query" case
	case "query":
		$columnString="*";
		if (isset($_POST['column']))  {
			$columnString = $_POST['column'];
			if (is_array($columnString))  {
				$columnString = implode(", ", $columnString);
			}
		}
		break;
	case "update":
		if (isset($_POST['values']) && isset($_POST['column']))  {
			$valuesString = $_POST['values'];
			$columnString = $_POST['column'];
			$fullString = "";
			if (is_array($valuesString) && is_array($columnString))  {
				for ($n=0; $n<sizeof($columnString); $n++) {
					$fullString .= ( ($n != 0) ? ", " : "" ) . $columnString[$n] . "=" . $valuesString[$n];
				}
			}
			else {
				$fullString = $columnString . "=" . $valuesString;
			}
		}
		break;
}
//connect to database
switch ($_POST['action']) {
	case "create":
		$db_query = $_POST['fullstatement'];
		break;
	case "sct":
		$db_query = "SHOW CREATE TABLE `" . $_POST['table'] . "`";
		break;
	case "drop":
		$db_query = "DROP TABLE `" . $_POST['table'] . "`";
		break;
	case "firstrow":
		$db_query = "SELECT * FROM `" . $_POST['table'] . "` LIMIT 1";
		$_POST['action'] = "query";
		break;
	case "insert":
		if (!$conditionString || trim(strval($conditionString)) == "")  {
		  $db_query = "INSERT INTO `" . $_POST['table'] . "` (" . $columnString . ") VALUES (" . $valuesString . ")";
		} else  {
		  $db_query = "INSERT INTO `" . $_POST['table'] . "` (" . $columnString . ") SELECT " . $valuesString . " FROM " . $_POST['table'] . " WHERE NOT EXISTS (SELECT " . $columnString . " FROM " . $_POST['table'] . " WHERE " . $conditionString . ") LIMIT 1";
		}
		break;
	case "update":
		$db_query = "UPDATE `" . $_POST['table'] . "` SET " . $fullString . "";
		if (isset($_POST['filter']))  {
			$db_query .=  " " . $_POST['filter'];
		}
		break;
	case "dbinfo":
		$db_query = "SHOW FULL TABLES FROM `" . $_POST['databasename'] ."`";
		break;
	case "delete":
		$db_query = "DELETE FROM `" . $_POST['table'] . "`";
		if (isset($_POST['filter']))  {
			$db_query .=  " " . $_POST['filter'];
		}
		break;
	case "query":
	default:
		$db_query = "SELECT ".$columnString." FROM `".$_POST['table'] . '` ';
		if (isset($_POST['filter']))  {
			$db_query .=  " " . $_POST['filter'];
		}
		break;
}
echo "<WADB_RESPONSE>\n"."\t<WADB_INFO connection=\"".urlencode($connectionText)."\">\n";
if (isset($_POST['table'])) {
	echo "\t\t<WADB_TABLE><![CDATA[".$_POST['table']."]]></WADB_TABLE>\n";
}
echo "\t\t<WADB_COLSTR><![CDATA[".$columnString."]]></WADB_COLSTR>\n".
		"\t\t<WADB_VALSTR><![CDATA[".$valuesString."]]></WADB_VALSTR>\n";
if (isset($_POST['filter'])) {
	echo "\t\t<WADB_FILTER><![CDATA[".$_POST['filter']."]]></WADB_FILTER>\n";
}
echo "\t\t<WADB_STATEMENT><![CDATA[".$db_query."]]></WADB_STATEMENT>\n"."\t</WADB_INFO>\n";
if (!extension_loaded('mysqli')) {
  $dbcontent = mysql_query($db_query,$connection);
} else {
  $dbcontent = $connection->query($db_query);
}
if ($connection->connect_errno) die("<WADB_RESPONSE><WADB_ERROR code=\"5\"><![CDATA[Failed to execute sql: (" . $connection->connect_errno . ") ".$db_query." : " . $connection->connect_error ."]]></WADB_ERROR></WADB_RESPONSE>");

switch ($_POST['action']) {
	case "query":
	case "sct":
	  
	if (!extension_loaded('mysqli')) {
		while ($row_dbcontent = mysql_fetch_assoc($dbcontent)) {
			echo("\t<WADB_DATA>\n");
			foreach ($row_dbcontent as $key => $value)  {
				echo('\t\t<WADB_COLUMN name="'.urlencode($key).'"><![CDATA['.$value."]]></WADB_COLUMN>\n");
			}
			echo("\t</WADB_DATA>\n");
		}
		mysql_free_result($dbcontent);
	} else {
		while ($row_dbcontent = mysqli_fetch_assoc($dbcontent)) {
			echo("\t<WADB_DATA>\n");
			foreach ($row_dbcontent as $key => $value)  {
				echo('\t\t<WADB_COLUMN name="'.urlencode($key).'"><![CDATA['.$value."]]></WADB_COLUMN>\n");
			}
			echo("\t</WADB_DATA>\n");
		}
		mysqli_free_result($dbcontent);
	}
		break;
	case "dbinfo":
	if (extension_loaded('mysqli')) {
		while ($row_dbcontent = mysqli_fetch_assoc($dbcontent)) {
			echo("\t<WADB_DATA>\n");
			foreach ($row_dbcontent as $key => $value)  {
				$dbcolcontent = $connection->query("SHOW COLUMNS FROM `" . $value . "`");
				if ($connection->connect_errno) die("<WADB_RESPONSE><WADB_ERROR code=\"5\"><![CDATA[Failed to execute sql: (" . $connection->connect_errno . ") ".$db_query." : " . $connection->connect_error ."]]></WADB_ERROR></WADB_RESPONSE>");
				
				$dballcols = array();
				if ($dbcolcontent) while ($row = $dbcolcontent->fetch_array()) {
					$dballcols[] = $row;
				}		
				if ($dbcolcontent) mysqli_free_result($dbcolcontent);
				echo('\t\t<WADB_COLUMN name="'.urlencode($value).'"><![CDATA['.json_encode($dballcols)."]]></WADB_COLUMN>\n");
				break;
			}
			echo("\t</WADB_DATA>\n");
		}
		mysqli_free_result($dbcontent);
	} else  {
		while ($row_dbcontent = mysql_fetch_assoc($dbcontent)) {
			echo("\t<WADB_DATA>\n");
			foreach ($row_dbcontent as $key => $value)  {
				$dbcolcontent = mysql_query("SHOW COLUMNS FROM `" . $value . "`",$connection);
				if ($connection->connect_errno) die("<WADB_RESPONSE><WADB_ERROR code=\"5\"><![CDATA[Failed to execute sql: (" . $connection->connect_errno . ") ".$db_query." : " . $connection->connect_error ."]]></WADB_ERROR></WADB_RESPONSE>");
				$dballcols =  mysql_fetch_all($dbcolcontent);
				mysql_free_result($dbcolcontent);
				echo('\t\t<WADB_COLUMN name="'.urlencode($value).'"><![CDATA['.json_encode($dballcols)."]]></WADB_COLUMN>\n");
			}
			echo("\t</WADB_DATA>\n");
		}
		mysql_free_result($dbcontent);
	}
		break;
	case "insert":
		if (extension_loaded('mysqli')) {
			echo("\t<WADB_DATA><![CDATA[".mysqli_insert_id($connection)."]]></WADB_DATA>\n");
		} else { 
			echo("\t<WADB_DATA><![CDATA[".mysql_insert_id($connection)."]]></WADB_DATA>\n");
		}
		break;
	case "create":
	case "drop":
	case "update":
	default:
		echo("\t<WADB_DATA><![CDATA[success]]></WADB_DATA>\n");
}
echo "</WADB_RESPONSE>";
?>