<?php
// version 2.22
class WA_MySQLi_Query  {
	public function __construct($conn, $mrt = "mrt") {
	  $this->Action = "";
	  $this->UseAction = "";
	  $this->AffectedRows = 0;
	  $this->Connection = $conn;
	  $this->Debug = false;
	  $this->EncryptionAlgorithm = "blowfish";
	  $this->EncryptionKey = "Change Me";
	  $this->EncryptionMode = "cbc";
	  $this->Error = "";
	  $this->ErrorMessage = "There is an error in your SQL syntax.";
	  $this->ErrorNo = 0;
	  $this->FieldCount = 0;
	  $this->Filter = "";
	  $this->FilterValues = array();
	  $this->ID = 0;
	  $this->InsertID = 0;
	  $this->MRTField = $mrt;
	  $this->NumRows = 0;
	  $this->ParamColumns = array();
	  $this->ParamCount = 0;
	  $this->ParamDefaults = array();
	  $this->ParamTypes = array();
	  $this->ParamValues = array();
	  $this->Prepared = true;
	  $this->RelationalColumns = array();
	  $this->RelationalKeyColumn = false;
	  $this->RelationalRows = array();
	  $this->RelationalRowsFound = false;
	  $this->RepeatConditions = array();
	  $this->RepeatedParams = array();
	  $this->RepeatIndex = 0;
	  $this->SelectedResult = false;
	  $this->Salt = false;
	  $this->SaveAs = "";
	  $this->Statement = "";
	  $this->Table = "";
	}
	
	public function addFilter($filterColumn, $filterComparison, $filterType, $filterValue, $filterRepeat = false, $temporary = false) {
    if ($filterType == 'i') {
      if (strval(intval($filterValue)) != $filterValue) {
        if ($this->Debug) {
          die("incorrect INTEGER value: " . $filterValue);
        } else {
          die($this->ErrorMessage);
        }
      }
    }
    if ($filterType == 'd') {
      if (strval(floatval($filterValue)) != $filterValue) {
        if ($this->Debug) {
          die("incorrect DOUBLE value: " . $filterValue);
        } else {
          die($this->ErrorMessage);
        }
      }
    }
    $this->FilterValues[] = array($filterColumn, $filterComparison, $filterType, $filterValue, $filterRepeat, $temporary);
	}
	
	private function addRelationalFilters() {
	  $filterValues = array();
	  for ($x=0; $x<sizeof($this->ParamColumns); $x++) {
		if ($this->ParamColumns[$x][0] == $this->RelationalColumns[0] || $this->ParamColumns[$x][0] == $this->RelationalColumns[1]) {
		  $filterValues[] = array($this->ParamColumns[$x][0], "=", $this->ParamTypes[$x], $this->ParamValues[$x], false, true);
		}
	  }
	  $this->FilterValues = $filterValues;
	}
	
	public function addRelationship($relationalParentColumn, $relationalChildColumn) {
      $this->RelationalColumns = array($relationalParentColumn, $relationalChildColumn);
	}
	
	private function addQuerystring($url) {
	   if (empty($_SERVER['QUERY_STRING'])) return $url;
	   if (strpos($url,"?")!==false) return $url . "&" . $_SERVER['QUERY_STRING'];
	   return $url . "?" . $_SERVER['QUERY_STRING'];
	}
	
	public function bindColumn($paramColumn, $paramType, $paramValue, $paramDefault, $mrtParam = false) {
    if ($this->isBound($paramColumn)) return;
    if ($mrtParam && !is_array($paramValue)) {
      $paramValue = array();
      $toCheck = isset($_POST[$this->MRTField])?$_POST[$this->MRTField]:array();
      for ($x=0; $x<sizeof($toCheck); $x++) {
        if (isset($_POST[$mrtParam."_".$toCheck[$x]])) {
          $paramValue[] = $_POST[$mrtParam."_".$toCheck[$x]];
        } else {
          $paramValue[] = "";
        }
      }
    }
    if (is_array($paramValue) && sizeof($paramValue) > 0) {
		  $this->RepeatedParams[] = array(sizeof($this->ParamColumns), $paramValue, false);
		  $paramValue = $paramValue[0];
	  }
	  $this->ParamColumns[] = array($paramColumn, false);
	  if ($paramType == "c") $paramType = "z";
	  $this->bindParam($paramType, $paramValue, $paramDefault);
	}
	
	private function bindDefault($paramType,$paramValue,$paramDefault) {
		if ($paramValue === "" || $paramValue === NULL) {
      if (($paramType == "d" || $paramType == "i") && $paramDefault == "WA_BLANK") $paramDefault = "WA_ZERO";
		  switch ($paramDefault) {
			case "WA_BLANK":

			case "WA_IGNORE":
			case "WA_DEFAULT":
			case "WA_TIMESTAMP":
			  $paramValue = "";
			  break;
			case "WA_NULL":
			  $paramValue = null;
			  break;
			case "WA_CURRENT_TIMESTAMP":
			  $paramValue = date("Y-m-d H:i:s");
			  break;
			case "WA_ZERO":
			  $paramValue = "0";
			  break;
			case "WA_NO":
			  $paramValue = "N";
			  break;
			default:
			  $paramValue = $paramDefault;
		  }
		}
		if ($paramType == "t") {
		  if ($paramValue) {
		    $hasTime = strpos($paramValue," ") !== false;
		    $paramValue = strtotime($paramValue);
		    if ($hasTime) {
			  $paramValue = date('Y-m-d H:i:s',$paramValue);
		    } else {
			  $paramValue = date('Y-m-d',$paramValue);
		    }
		  } else {
        $paramValue = null;
      }
		} else if ($paramType == "c") {
		  $paramValue = "%" . $paramValue . "%";
		} else if ($paramType == "b") {
		  $paramValue = $paramValue . "%";
		} else if ($paramType == "e") {
		  $paramValue = "%" . $paramValue;
		} else if ($paramType == "y") {
		    if ($paramValue) {
		      $paramValue = "Y";
		    } else {
		      $paramValue = "N";
		    }
		} else if ($paramType == "n" || $paramType == "z") {
		    if ($paramValue) {
		      $paramValue = ($paramType == "n")?"-1":"1";
		    } else {
		      $paramValue = "0";
		    }
		} 
		return $paramValue;
	}
	
	public function bindParam($paramType,$paramValue,$paramDefault="",$paramPosition=false) {
	  $paramArray = array($paramValue);
	  $isList = false;
	  if (strpos($paramType,"l")) {
		$paramType = substr($paramType,0,1);
		$paramArray = preg_split("/\s*\,\s*/", $paramValue);
		$isList = true;
	  }
	  for ($x=0; $x<sizeof($paramArray); $x++) {
      $paramValue = $paramArray[$x];
      $paramValue = $this->bindDefault($paramType,$paramValue,$paramDefault);
      if (($isList || sizeof($paramArray) > 1) && $x == 0) {
        $sqlParts = explode("?",$this->Statement);
        if (!preg_match("/\(\s*$/",$sqlParts[sizeof($this->ParamValues)]) && !preg_match("/^\s*\)/",$sqlParts[sizeof($this->ParamValues)+1])) {
          $sqlParts[sizeof($this->ParamValues)] =  $sqlParts[sizeof($this->ParamValues)] . "(";
          $sqlParts[sizeof($this->ParamValues)+1] = ")" . $sqlParts[sizeof($this->ParamValues)+1];
        }
        $this->Statement = implode("?",$sqlParts);
      }
      if ($x>0) {
        $sqlParts = explode("?",$this->Statement);
        $sqlParts[sizeof($this->ParamValues)] = ", ?" . $sqlParts[sizeof($this->ParamValues)];
        $this->Statement = implode("?",$sqlParts);
      }
      if ($paramPosition == false) {
        $this->ParamTypes[] = $paramType;	
        $this->ParamValues[] = $paramValue;
        $this->ParamDefaults[] = $paramDefault;
      } else {
        array_splice($this->ParamTypes, $paramPosition, 0, $paramType);
        array_splice($this->ParamValues, $paramPosition, 0, $paramValue);
        array_splice($this->ParamDefaults, $paramPosition, 0, $paramDefault);
      }
	  }
	}
	
	public function checkRepeatConditions() {
	  for ($x=0; $x<sizeof($this->RepeatConditions); $x++) {
		if (!(isset($_POST[$this->RepeatConditions[$x] . $this->RepeatIndex]) || isset($_GET[$this->RepeatConditions[$x] . $this->RepeatIndex]))) return false;
	  }
	  return true;
	}
	
	private function clearRepeatedFilters() {
	  for ($x=sizeof($this->RepeatedParams)-1; $x>=0; $x--) {
		if ($this->RepeatedParams[$x][2]) {
		  array_splice($this->ParamValues, $this->RepeatedParams[$x][0], 1);
		  array_splice($this->ParamTypes, $this->RepeatedParams[$x][0], 1);
		  array_splice($this->ParamDefaults, $this->RepeatedParams[$x][0], 1);
		  array_splice($this->RepeatedParams, $x, 1);
		}
	  }
	}
	
	private function clearTemporaryFilters() {
	  for ($x=sizeof($this->FilterValues)-1; $x>=0; $x--) {
		if ($this->FilterValues[$x][5]) {
		  array_pop($this->ParamValues);
		  array_pop($this->ParamTypes);
		  array_pop($this->ParamDefaults);
		  array_pop($this->FilterValues);
		}
	  }
	}
	
	private function clearTemporaryColumns() {
	  for ($x=sizeof($this->ParamColumns)-1; $x>=0; $x--) {
		if ($this->ParamColumns[$x][1]) {
		  array_splice($this->ParamColumns, $x, 1);
		  array_splice($this->ParamTypes, $x, 1);
		  array_splice($this->ParamDefaults, $x, 1);
		  array_splice($this->ParamValues, $x, 1);
		}
	  }
	}
	
	public function createStatement() {
	  $this->UseAction = $this->Action;
	  if (strtolower($this->UseAction) == "relational") {
      if (!$this->RelationalRowsFound) {
        $this->RelationalRowsFound = true;
        if (!class_exists("WA_MySQLi_RS")) require(dirname(__FILE__) . "/" . "rsobj.php");
        $KeyRS = new WA_MySQLi_RS("KeyRS",$this->Connection,0);
        $KeyRS->setQuery("SHOW KEYS FROM " . $this->Table ." WHERE Key_name = 'PRIMARY'");
        $KeyRS->execute();
        $this->RelationalKeyColumn = $KeyRS->getColumnVal("Column_name");

        if (!$this->RelationalKeyColumn) {
          $ReplaceDelete = new WA_MySQLi_Query($this->Connection);
          $ReplaceDelete->setQuery("DELETE FROM " . $this->Table);
          if (sizeof($ReplaceDelete->FilterValues) >= 1) {
            $ReplaceDelete->setFilter();
            $ReplaceDelete->execute(); 
          }
        } else {
          if (!$this->SelectedResult) {
            $RelationalRS = new WA_MySQLi_RS("RelationalRS",$this->Connection,0);
            $RelationalRS->setQuery("SELECT " . $this->RelationalColumns[1] . ", " .$this->RelationalKeyColumn. " FROM " . $this->Table);
            $RelationalRS->FilterValues = array($this->getParentFilterFromRelational());
            $RelationalRS->setFilter();
            $RelationalRS->execute();
            $this->SelectedResult = $RelationalRS;
          }
          for ($x=0; $x<sizeof($this->SelectedResult->Results); $x++) {
          $this->RelationalRows[] = array($this->SelectedResult->Results[$x][$this->RelationalColumns[1]], false, $this->SelectedResult->Results[$x][$this->RelationalKeyColumn]);
        }
        }
      }
      $relationalChildValue = -1;
      for ($x=0; $x<sizeof($this->ParamColumns); $x++) {
        if ($this->ParamColumns[$x][0] == $this->RelationalColumns[1]) {
          if (empty($this->ParamValues[$x])) {
            $this->Statement = "";
            return;
          }
          $relationalChildValue = $this->ParamValues[$x];
        }
        if ($this->ParamColumns[$x][0] == $this->RelationalColumns[0] && empty($this->ParamValues[$x])) {
          $this->Statement = "";
          return;
        }
      }
      $childFound = false;
      for ($x=0; $x<sizeof($this->RelationalRows); $x++) {
        if ($relationalChildValue == $this->RelationalRows[$x][0]) {
        $this->RelationalRows[$x][1] = true;
        $childFound = true;
        break;
        }
      }
      if ($childFound) {
        $this->addRelationalFilters();
        $this->UseAction = "update";
      } else {
        $this->UseAction = "insert";
      }
	  }
	  if (strtolower($this->UseAction) == "conditional") {
		if (sizeof($this->FilterValues) > 0) {
		  if (!class_exists("WA_MySQLi_RS")) require(dirname(__FILE__) . "/" . "rsobj.php");
		  $ConditionalRS = new WA_MySQLi_RS("ConditionalRS",$this->Connection,1);
		  $ConditionalRS->setQuery("SELECT Count(*) AS RowCount FROM " . $this->Table);
		  $ConditionalRS->FilterValues = $this->FilterValues;
		  $ConditionalRS->setFilter();
		  $ConditionalRS->execute();
		  if ($ConditionalRS->getColumnVal("RowCount")) {
		    $this->UseAction = "update";
		  } else {
			$this->FilterValues = array();
		    $this->UseAction = "insert";
		  }
		} else {
		  $this->UseAction = "insert";
		}
	  }
	  if (strtolower($this->UseAction) == "replace") {
		if (sizeof($this->FilterValues) > 0) { 
		  $ReplaceDelete = new WA_MySQLi_Query($this->Connection);
		  $ReplaceDelete->setQuery("DELETE FROM " . $this->Table);
		  $ReplaceDelete->FilterValues = $this->FilterValues;
		  $ReplaceDelete->setFilter();
		  $ReplaceDelete->execute();
		}
		$this->UseAction = "insert";
	  }
	  switch (strtolower($this->UseAction)) {
		case "update":
		  $Columns = "";
		  $this->Statement = "UPDATE " . $this->Table . " SET ";
		  $oneAdded = false;
		  for ($x=0; $x<sizeof($this->ParamColumns); $x++) {
		    if (!($this->ParamDefaults[$x] == "WA_IGNORE" && $this->ParamValues[$x] === "")) {
              if ($Columns != "") $Columns .= ", ";
			  $columnRef = $this->ParamColumns[$x][0];
			  if (strpos($columnRef,"`") == false && strpos($columnRef,"(") == false) $columnRef = '`'.$columnRef.'`';
			  $colPlaceholder = "?";
			  if ($this->ParamDefaults[$x] == "WA_DEFAULT" && ($this->ParamValues[$x] === "" || $this->ParamValues[$x] === null || $this->ParamValues[$x] === false)) $colPlaceholder = "DEFAULT";
			  if ($this->ParamDefaults[$x] == "WA_TIMESTAMP" && $this->ParamValues[$x] == "") $colPlaceholder = "SYSDATE()";
              $Columns .= $columnRef . " = " . $colPlaceholder;
		      $oneAdded = true;
			}
		  }
		  if (!$oneAdded) {
			$this->Statement = false;
			return;
		  }
		  $this->Statement .= $Columns;
		  break;
		case "insert":
		  $Columns = "";
		  $Values = "";
		  $this->Statement = "INSERT INTO " . $this->Table . " (";
		  $oneAdded = false;
		  for ($x=0; $x<sizeof($this->ParamColumns); $x++) {
		    if (!($this->ParamDefaults[$x] == "WA_IGNORE" && $this->ParamValues[$x] === "")) {
              if ($Columns != "") {
			    $Columns .= ", ";
			    $Values .= ", ";
			  }
			  $columnRef = $this->ParamColumns[$x][0];
			  if (strpos($columnRef,"`") == false && strpos($columnRef,"(") == false) $columnRef = '`'.$columnRef.'`';
              $Columns .= $columnRef;
			  if ($this->ParamDefaults[$x] == "WA_DEFAULT" && ($this->ParamValues[$x] === "" || $this->ParamValues[$x] === null || $this->ParamValues[$x] === false)) {
				  $Values .= "DEFAULT";
			  } else if ($this->ParamDefaults[$x] == "WA_TIMESTAMP")	{
				$Values .= "SYSDATE()";
			  } else {
				$Values .= "?";
			  }
		      $oneAdded = true;
			}
		  }
		  if (!$oneAdded) {
			$this->Statement = false;
			return;
		  }
		  $this->Statement .= $Columns . ") VALUES (" . $Values . ")";
		  break;
		case "delete":
		  $this->Statement = "DELETE FROM " . $this->Table;
		  if (sizeof($this->ParamColumns) > 0) $this->Statement .= " WHERE ";
		  for ($x=0; $x<sizeof($this->ParamColumns); $x++) {
			if ($x!=0) $this->Statement .= " AND ";
			$columnRef = $this->ParamColumns[$x][0];
			if (strpos($columnRef,"`") == false && strpos($columnRef,"(") == false) $columnRef = '`'.$columnRef.'`';
            $this->Statement .= $columnRef . " = ?";
		  }
		  break;
	  }
      $this->setFilter();
	}
	
	public function debugSQL() {
	  $statement = $this->Statement;
    $paramLen = 1;
    for ($x=0; $x<sizeof($this->ParamValues); $x++) {
      if (is_array($this->ParamValues[$x])) $paramLen = sizeof($this->ParamValues[$x]);
    }
    for ($x=0; $x<$paramLen; $x++) {
      $params = ($this->getParams($x));
      $paramTypes = $params[0];
      $startStatement = "";
      $endStatement = $statement;
      for ($x=0; $x<strlen($paramTypes); $x++) {
        $pos = strpos($endStatement, "?");
        if ($pos !== false) {
          $replace = $this->getSQLValue($params[$x+1],$paramTypes[$x],true);
          $startStatement .= substr($endStatement,0,$pos) . $replace;
          $endStatement = substr($endStatement, $pos + 1);
        }
      }
      $statement = $startStatement . $endStatement;
    }
	  $statement = $startStatement . $endStatement;
	  return $statement;
	}
	
	public function execute($allowTableOverwrite=false) {
	  $shouldRun = true;
	  $startStatement = $this->Statement;
	  while ($this->incrementRepeat()) {
      if (sizeof($this->RepeatedParams) > 0) {
        if (!$this->checkRepeatConditions()) {
          continue;
        }
        $this->clearRepeatedFilters();
        $this->resetRepeated();
      }
      if (!$startStatement) {
        $this->clearTemporaryColumns();
        $this->createStatement();
        if (!$this->Statement) continue;
        if ((strtolower($this->UseAction) == "delete" || strtolower($this->UseAction) == "update") && strpos($this->Statement," WHERE ") === false && !$allowTableOverwrite) {
          continue;
        }
      }
      if (in_array("WA_SKIP",$this->ParamValues)) continue;
      if (!$this->Prepared) {
        $statement = $this->Statement;
        if (sizeof($this->ParamValues) > 0) {
          $paramLen = 1;
          for ($x=0; $x<sizeof($this->ParamValues); $x++) {
            if (is_array($this->ParamValues[$x])) $paramLen = sizeof($this->ParamValues[$x]);
          }
          for ($x=0; $x<$paramLen; $x++) {
            $params = ($this->getParams($x));
            $paramTypes = $params[0];
            $startStatement = "";
            $endStatement = $statement;
            for ($x=0; $x<strlen($paramTypes); $x++) {
              $pos = strpos($endStatement, "?");
              if ($pos !== false) {
                $replace = $this->getSQLValue($params[$x+1],$paramTypes[$x]);
                $startStatement .= substr($endStatement,0,$pos) . $replace;
                $endStatement = substr($endStatement, $pos + 1);
              }
            }
          $statement = $startStatement . $endStatement;
          }
        }
        $query = $this->Connection->query($statement);
        if ($query == false) {
          if ($this->Debug) {
            die($this->debugSQL() . "<BR><BR>" . mysqli_error($this->Connection));
          } else {
            die($this->ErrorMessage);
          }
        } else {
          $query = false;
        }
      } else {
        if (sizeof($this->ParamTypes)) {
          $query = $this->Connection->Prepare($this->Statement);
          if ($query == false) {
            if ($this->Debug) {
              die($this->debugSQL() . "<BR><BR>" . mysqli_error($this->Connection));
            } else {
              die($this->ErrorMessage);
            }
          }
          $paramLen = 1;
          for ($x=0; $x<sizeof($this->ParamValues); $x++) {
            if (is_array($this->ParamValues[$x])) $paramLen = sizeof($this->ParamValues[$x]);
          }
          for ($x=0; $x<$paramLen; $x++) {
            call_user_func_array(array($query, "bind_param"),$this->paramRefs($this->getParams($x)));
            $query->execute();
            if ($query->errno) {
              if ($this->Debug) {
                die($this->debugSQL() . "<BR><BR>" . $query->error);
              } else {
                die($this->ErrorMessage);
              }
            }
          }
            $this->clearTemporaryFilters();
        } else {
          $query = $this->Connection->query($this->Statement);
          $query = false;
        }
      }
      if ($this->SaveAs != "" && strtolower($this->UseAction) == "insert") {
        @session_start();
        $_SESSION[$this->SaveAs] = $query?$query->insert_id:$this->Connection->insert_id;
      }
      $this->AffectedRows = $query?$query->affected_rows:$this->Connection->affected_rows;
      $this->AffectedRows = ($this->AffectedRows===-1)?0:$this->AffectedRows;
      $this->InsertID = $query?$query->insert_id:$this->Connection->insert_id;
      $this->NumRows = $query?$query->num_rows:0;
      $this->ParamCount = $query?$query->param_count:0;
      $this->FieldCount = $query?$query->field_count:$this->Connection->field_count;
      $this->Error = $query?$query->error:$this->Connection->error;
      $this->ErrorNo = $query?$query->errno:$this->Connection->errno;
      $this->ID = $query?$query->id:$this->Connection->thread_id;
      if ($query && method_exists($query,"close")) $query->close();
	  }
	  

	  if (sizeof($this->RelationalRows)) {		
      $this->addRelationalFilters();
      for ($x=0; $x<sizeof($this->RelationalRows); $x++) {
        if ($this->RelationalRows[$x][1] == false) {
        $CleanUpQuery = new WA_MySQLi_Query($this->Connection);
        $CleanUpQuery->Statement = "delete FROM " .  $this->Table . " WHERE " . $this->RelationalKeyColumn . " = " . $this->RelationalRows[$x][2];
        $CleanUpQuery->execute();
        }
      }
	  }
	}
	
	private function getChildFilterFromRelational($row) {
	  for ($x=0; $x<sizeof($this->ParamColumns); $x++) {
		if ($this->ParamColumns[$x][0] == $this->RelationalColumns[1]) {
		  return array($this->RelationalColumns[1], "=", $this->ParamTypes[$x], $this->RelationalRows[$row][0], false, true);
		}
	  }
      return array($this->RelationalColumns[0], "=", "i", "-1", false, true);
	}
  
  public function getSalt() {
    $isStrong = false;
    $this->Salt = base64_encode(openssl_random_pseudo_bytes(openssl_cipher_iv_length($this->EncryptionAlgorithm),$isStrong));
  }

  public function getEncryptedVal($val) {
    $retVal = $val;
    $salt = "";
    if ($this->Salt) $salt = base64_decode($this->Salt);
    if ($retVal !== "") {
      $retVal = base64_encode(openssl_encrypt($val,$this->EncryptionAlgorithm,$this->EncryptionKey, OPENSSL_RAW_DATA, $salt));
    }

    return $retVal; 
  }
	
	private function getParentFilterFromRelational() {
	  for ($x=0; $x<sizeof($this->ParamColumns); $x++) {
		if ($this->ParamColumns[$x][0] == $this->RelationalColumns[0]) {
		  return array($this->RelationalColumns[0], "=", $this->ParamTypes[$x], $this->ParamValues[$x], false, true);
		}
	  }
      return array($this->RelationalColumns[0], "=", "i", "-1", false, true);
	}
	
	private function getParams($index) {
	  $useParams = array();
	  $useTypes = array();
	  for ($x=0; $x<sizeof($this->ParamValues); $x++) {
		$paramVal = is_array($this->ParamValues[$x])?$this->ParamValues[$x][$index]:$this->ParamValues[$x];
		if (!(($paramVal === "" || $paramVal === null || $paramVal === false) && ($this->ParamDefaults[$x] == "WA_DEFAULT" || $this->ParamDefaults[$x] == "WA_IGNORE" || $this->ParamDefaults[$x] == "WA_TIMESTAMP"))) {
		  $useParams[] = $paramVal;
		  $typesVal = $this->ParamTypes[$x];
		  if ($typesVal == "t" || $typesVal == "c" || $typesVal == "b" || $typesVal == "e" || $typesVal == "y") {
		    $typesVal = "s";
		  } else if ($typesVal == "n" || $typesVal == "z") {
		    $typesVal = "i";
		  } else if ($typesVal != "i" && $typesVal != "d" && $typesVal != "s" && $typesVal != "b") {
		    $typesVal = "s";
		  }
		  $useTypes[] = $typesVal;
		}
	  }
	  return array_merge(array(implode("",$useTypes)), $useParams);
	}
  
  public function getSelected() {
    if (!$this->SelectedResult) {
      $RelationalRS = new WA_MySQLi_RS("RelationalRS",$this->Connection,0);
      $RelationalRS->setQuery("SELECT " . $this->RelationalColumns[1] . ($this->RelationalKeyColumn?", " .$this->RelationalKeyColumn:""). " FROM " . $this->Table);
      $RelationalRS->FilterValues = array($this->getParentFilterFromRelational());
      $RelationalRS->setFilter();
      $RelationalRS->execute();
      $this->SelectedResult = $RelationalRS;
    }
    return $this->SelectedResult->getColumnArray($this->RelationalColumns[1]);
  }
  
  public function getSQLValue($val,$type,$forDebug=false) {
    if (is_null($val)) {
      $retval = "NULL";
    } else if ($type == 'i') {
      if (!$forDebug && strval(intval($val)) != $val) {
        if ($this->Debug) {
          die($this->debugSQL() . "<BR><BR>" . "Truncated incorrect INTEGER value: " . $val);
        } else {
          die($this->ErrorMessage);
        }
      }
      $retval = intval($val);
    } else if ($type == 'd') {
      if (!$forDebug && strval(floatval($val)) != $val) {
        if ($this->Debug) {
          die($this->debugSQL() . "<BR><BR>" . "Truncated incorrect DOUBLE value: " . $val);
        } else {
          die($this->ErrorMessage);
        }
      }
      $retval = floatval($val);
    } else {
      $retval = "'" . mysqli_real_escape_string($this->Connection,$val) . "'";
    }
    return $retval;
  }
	
	private function incrementRepeat() {
	  $this->RepeatIndex++;
	  $totalRepeats = 1;
    // check action
    if ($this->Action == "relational") {
      $totalRepeats = sizeof($this->RepeatedParams[0][1]);
    } else {
      for ($x=0; $x<sizeof($this->RepeatedParams); $x++) {
        $totalRepeats *= sizeof($this->RepeatedParams[$x][1]);
      }
    }
	  return ($this->RepeatIndex <= $totalRepeats);
	}
  
    public function isBound($column) {
      for ($x=0; $x<sizeof($this->ParamColumns); $x++) {
        if ($this->ParamColumns[$x][0] == $column) return true;
      }
      return false;
    }
	
	public function paramRefs($arr) {
	  if (strnatcmp(phpversion(),'5.3') >= 0) {
		  $refs = array();
		  foreach($arr as $key => $value) $refs[$key] = &$arr[$key];
		  return $refs;
	  }
	  return $arr;
	}
	
	public function redirect($url,$keepQuerystring=false) {
	  if ($url) {
      $url = str_replace("[InsertID]",$this->InsertID,$url);
      $url = str_replace("[Insert_ID]",$this->InsertID,$url);
      if ($keepQuerystring) {
        header("location: " . $this->addQuerystring($url));
      }
      header("location: " . $url);
      die();
	  }
	}
	
	private function resetCombinations($arrays, $i = 0) {
      if (!isset($arrays[$i])) {
        return array();
      }
      if ($i == count($arrays) - 1) {
        return $arrays[$i];
      }
      if ($this->Action === "relational") {
        for ($x=0; $x<sizeof($arrays[0]); $x++) {
          $row = array();
          for ($y=0; $y<sizeof($arrays); $y++) {
            $row[] = $arrays[$y][$x];
          }
          $result[] = $row;
        }
      } else {
        $tmp = $this->resetCombinations($arrays, $i + 1);
        $result = array();
        foreach ($arrays[$i] as $v) {
          foreach ($tmp as $t) {
              $result[] = is_array($t) ? 
                  array_merge(array($v), $t) :
                  array($v, $t);
          }
        }
      }
      return $result;
    }
	
	public function resetRepeated() {
	  $index = 0;
	  $combinations = array();
	  for ($x=0; $x<sizeof($this->RepeatedParams); $x++) {
		  $combinations[] = $this->RepeatedParams[$x][1];
	  }
    $allParams = $this->resetCombinations($combinations);
	  for ($x=0; $x<sizeof($this->RepeatedParams); $x++) {
      $newVal = $allParams[$this->RepeatIndex-1];
      if (is_array($newVal)) $newVal = $newVal[$x];
      if ($this->RepeatedParams[$x][2]) {
        $this->bindParam($this->ParamColumns[$this->RepeatedParams[$x][0]],$this->ParamTypes[$this->RepeatedParams[$x][0]],$newVal,$this->ParamDefaults[$this->RepeatedParams[$x][0]]);
      } else {
        $this->ParamValues[$this->RepeatedParams[$x][0]] = $this->bindDefault($this->ParamTypes[$this->RepeatedParams[$x][0]],$newVal,$this->ParamDefaults[$this->RepeatedParams[$x][0]]);
      }
	  }
	}
	
	public function saveInSession($varname) {
	  $this->SaveAs = $varname;	
	}
	
	public function setFilter() {
	  if (sizeof($this->FilterValues) > 0) {
      $this->Statement .= " WHERE ";
      for ($x=0; $x<sizeof($this->FilterValues); $x++) {
        if (!is_array($this->FilterValues[$x][3])) {
          $filterValues = array($this->FilterValues[$x][3]);
        } else {
          $filterValues = $this->FilterValues[$x][3];
        }
        for ($y=0; $y<sizeof($filterValues); $y++) {
          if ($x>0 && $y==0) $this->Statement .= " AND ";
          if (sizeof($filterValues)>1 && $y==0) $this->Statement .= "(";
          if ($y>0) $this->Statement .= ($this->FilterValues[$x][1] == "<>" || strtoupper($this->FilterValues[$x][1]) == "IS NOT")?" AND ":" OR ";
          if ($this->FilterValues[$x][4]) $this->RepeatedParams[] = array(sizeof($this->ParamValues),$this->FilterValues[$x][4],true);
          $columnRef = $this->FilterValues[$x][0];
          if (strpos($columnRef,"`") == false && strpos($columnRef,"(") == false) $columnRef = '`'.$columnRef.'`';
          $this->Statement .= $columnRef . " " . $this->FilterValues[$x][1] . " ?";
          $this->bindParam($this->FilterValues[$x][2], strval($filterValues[$y]), "");
        }
        if (sizeof($filterValues)>1) $this->Statement .= ")";
		  }
	  }
	}
	
	public function setQuery($statement) {
	  $this->Statement = $statement;	
	}
	
	public function setRepeatCondition($conditionalField) {
	  $this->RepeatConditions[] = $conditionalField;
	}
	
}
?>