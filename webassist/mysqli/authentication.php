<?php
// version 2.14

function WA_MySQLi_Auth_Check($name) {
  @session_start();
  $restrictFailed = true;
  if (is_array($name)) {
    for ($x=0; $x<sizeof($name); $x++) {
      if (isset($_SESSION["WA_AUTH_".$name[$x]])) {
        $restrictFailed = false;
      }
    }
  } else {
    if (isset($_SESSION["WA_AUTH_".$name])) {
      $restrictFailed = false;
    }
  }
  @session_commit();
  return !$restrictFailed;
}

class WA_MySQLi_Auth  {
	public function __construct($conn=false) {
	  $this->Action = "";
	  $this->AutoReturn = "";
	  $this->AutoLogin = false;
	  $this->AutoTrigger = false;
	  $this->CipherValues= array();
	  $this->Connection = $conn;
	  $this->CookieDomain = "";
	  $this->CookieExpires = time()+(60*60*24*30);
	  $this->CookieHTTPOnly = 0;
	  $this->CookiePath = "/";
	  $this->CookieSecure = 0;
	  $this->CurrentPage = $_SERVER['REQUEST_URI'];
	  $this->Debug = true;
	  $this->EncryptionAlgorithm = "blowfish";
	  $this->EncryptionKey = "Change Me";
	  $this->EncryptionMode = "cbc";
	  $this->FailRedirect = "";
	  $this->FilterValues= array();
	  $this->HashValues= array();
	  $this->IsAuto= false;
	  $this->Log= array();
	  $this->Name = "";
	  $this->NoSuccessRedirect = false;
	  $this->ParamTypes = "";
	  $this->ParamValues= array();
	  $this->PassURLParameters = true;
	  $this->Prepared = true;
	  $this->Redirect = "";
	  $this->RememberMe = false;
	  $this->Result = false;
	  $this->SaveLogin = false;
	  $this->Statement = "";
	  $this->StoreValues= array();
	  $this->SuccessRedirect = "";
	  $this->Table = "";
	  $this->Trigger = true;
	}
	
	public function addCipher($hashColumn, $hashComparison, $salt) {
    $this->CipherValues[] = array($hashColumn, $hashComparison, $salt);
	}
	
	public function addFilter($filterColumn, $filterComparison, $filterType, $filterValue) {
    $this->FilterValues[] = array($filterColumn, $filterComparison, $filterType, $filterValue);
	}
	
	public function addHash($hashColumn, $hashComparison) {
    $this->HashValues[] = array($hashColumn, $hashComparison);
	}
	
	public function addName($nameValue) {
	  if (!is_array($this->Name)) {
	    if ($this->Name) {
		    $this->Name = array($this->Name);
	    } 
	  } 
	  $this->Name[] = $nameValue;
	}
	
	private function addQuerystring($url) {
	   if (empty($_SERVER['QUERY_STRING'])) return $url;
	   if (strpos($url,"?")!==false) {
		   $URLParams = substr($url,strpos($url,"?")+1);
		   $url = substr($url,0,strpos($url,"?"));
		   $URLParamArray = explode("&",$URLParams);
	       $QSParamArray = explode("&",$_SERVER['QUERY_STRING']);
		   $AddedParamArray = array();
		   $EndQueryString = "?".$URLParams;
		   for ($x=0; $x<sizeof($URLParamArray); $x++) {
			   $URLParam = substr($URLParamArray[$x],0,strpos($URLParamArray[$x],"="));
			   $AddedParamArray[$URLParam] = true;
		   }
		   for ($x=0; $x<sizeof($QSParamArray); $x++) {
			   $URLParam = substr($QSParamArray[$x],0,strpos($QSParamArray[$x],"="));
			   if (!array_key_exists($URLParam,$AddedParamArray)) {
				  $EndQueryString .= "&" . $QSParamArray[$x];
			   }
		   }
		   return $url . $EndQueryString;
	   }
	   return $url . "?" . $_SERVER['QUERY_STRING'];
	}
	
	public function bindParam($paramType,$paramValue) {
	  if ($paramType == "t") {
      $paramType = "s";
      $hasTime = strpos($paramValue," ") !== false;
      $paramValue = strtotime($paramValue);
      if ($hasTime) {
        $paramValue = date('Y-m-d H:i:s',$paramValue);
      } else {
        $paramValue = date('Y-m-d',$paramValue);
      }
	  }
	  $this->ParamTypes .= $paramType;	
	  $this->ParamValues[] = $paramValue;	
	}
	
	public function execute() {
	  switch ($this->Action) {
		case "authenticate":
		  if (isset($_GET["accesscheck"])) {
			  @session_start();
			  $_SESSION["WA_FAIL_".$this->Name] = $_GET["accesscheck"];
			  @session_commit();
		  }
		  if ($this->Trigger) {
			  for ($x=0; $x<sizeof($this->FilterValues); $x++) {
          if ($this->RememberMe) {
            $this->saveCookie("RememberMe_".$this->FilterValues[$x][0],$this->FilterValues[$x][3]);
          } else {
            $this->saveCookie("RememberMe_".$this->FilterValues[$x][0],"");
          }
          if ($this->SaveLogin) {
            $this->saveCookie("AutoLogin_".$this->FilterValues[$x][0],$this->FilterValues[$x][3]);
          } else {
            $this->saveCookie("AutoLogin_".$this->FilterValues[$x][0],"");
          }
			  } 
		  } else {
		    if ($this->AutoLogin && !WA_MySQLi_Auth_Check($this->Name)) {
          $this->Trigger = true;
          for ($x=0; $x<sizeof($this->FilterValues); $x++) {
            $this->FilterValues[$x][3] = ((isset($_COOKIE["AutoLogin_".$this->FilterValues[$x][0]]))?$_COOKIE["AutoLogin_".$this->FilterValues[$x][0]]:"");
          if (!isset($_COOKIE["AutoLogin_".$this->FilterValues[$x][0]])) $this->Trigger = false;
          }
          $this->IsAuto = true;
			  }
		  }
		  if (!$this->Trigger) return false;
		  if (!$this->Statement)  {
			$this->Statement = "SELECT ";
			$colsAdded = array();
			if (sizeof($this->StoreValues) > 0) {
			  for ($x=0; $x<sizeof($this->StoreValues); $x++) {
				  if (!array_key_exists($this->StoreValues[$x][0],$colsAdded) && !$this->StoreValues[$x][2]) {
					  if ($x>0) $this->Statement .= ", ";
					  $this->Statement .= $this->StoreValues[$x][0];
					  $colsAdded[$this->StoreValues[$x][0]] = true;
				  }
			  }
			  for ($x=0; $x<sizeof($this->HashValues); $x++) {
				  if (!array_key_exists($this->HashValues[$x][0],$colsAdded)) {
					  $this->Statement .= ", ";
					  $this->Statement .= $this->HashValues[$x][0];
					  $colsAdded[$this->HashValues[$x][0]] = true;
				  }
			  }
			  for ($x=0; $x<sizeof($this->CipherValues); $x++) {
				  if (!array_key_exists($this->CipherValues[$x][0],$colsAdded)) {
					  $this->Statement .= ", ";
					  $this->Statement .= $this->CipherValues[$x][0];
					  $colsAdded[$this->CipherValues[$x][0]] = true;
					  if (!array_key_exists($this->CipherValues[$x][2],$colsAdded)) {
					    $this->Statement .= ", ";
					    $this->Statement .= $this->CipherValues[$x][2];
					    $colsAdded[$this->CipherValues[$x][2]] = true;
					  }
				  }
			  }
			} else {
			  $this->Statement .= "*";
			}
			$this->Statement .= " FROM " . $this->Table;
			$this->setFilter();
			$this->Statement .= " LIMIT 1";
		  }

		  if (!$this->Prepared) {
			$statement = $this->Statement;
			if (sizeof($this->ParamValues) > 0) {
			  $params = ($this->getParams());
			  $paramTypes = $params[0];
			  $startStatement = "";
			  $endStatement = $statement;
			  for ($x=0; $x<strlen($paramTypes); $x++) {
				  $pos = strpos($endStatement, "?");
				  if ($pos !== false) {
            if (is_null($params[$x+1])) {
              $replace = "NULL";
            } else if ($paramTypes[$x] == 'i') {
              $replace = intval($params[$x+1]);
            } else if ($paramTypes[$x] == 'd') {
              $replace = floatval($params[$x+1]);
            } else {
              $replace = "'" . mysqli_real_escape_string($this->Connection,$params[$x+1]) . "'";
            }
            $startStatement .= substr($endStatement,0,$pos) . $replace;
				    $endStatement = substr($endStatement, $pos + 1);
				  }
			  }
			  $statement = $startStatement . $endStatement;
			}
			$query = $this->Connection->query($statement);
			  if ($query == false) {
				  if ($this->Debug) {
				    die($statement . "<BR><BR>" . mysqli_error($this->Connection));
				  } else {
				    die("There is an error in your SQL syntax.");
				  }
			  }
			  if ($rows =  mysqli_fetch_assoc($query)) {
			   $this->Result = $rows;
			  }
		  } else {
        $query = $this->Connection->Prepare($this->Statement);
        if ($query == false) {
          if ($this->Debug) {
            die($this->Statement . "<BR><BR>" . mysqli_error($this->Connection));
          } else {
            die("There is an error in your SQL syntax.");
          }
        }
        if ($this->ParamTypes) call_user_func_array(array($query, "bind_param"),$this->paramRefs($this->getParams()));
        $query->execute();
        if (method_exists($query,'get_result')) {
          $result = $query->get_result();
          $this->Result = $result->fetch_array(MYSQLI_ASSOC);
        } else {
          $result = $this->wa_mysqli_stmt_get_result($query);
          $this->Result = $this->wa_mysqli_result_fetch_assoc($result);
        }
		  }
		  $query->close();
		  $hashPass = true;
		  if ($this->Result) {
		    for ($x=0; $x<sizeof($this->HashValues); $x++) {
			    if (!password_verify($this->HashValues[$x][1], $this->Result[$this->HashValues[$x][0]])) {
			      $hashPass = false;
			      break;
			    }
		    }
		    if ($hashPass) for ($x=0; $x<sizeof($this->CipherValues); $x++) {
			    $CompareTo = $this->Result[$this->CipherValues[$x][0]];
			    $CompareVal = base64_encode(mcrypt_encrypt($this->EncryptionAlgorithm,$this->EncryptionKey,$this->CipherValues[$x][1],$this->EncryptionMode,base64_decode($this->Result[$this->CipherValues[$x][2]])));
			    if ($CompareTo !== $CompareVal) {
			      $hashPass = false;
			      break;
			    }
		    }
		  }
		  if ($this->Result && $hashPass) {
        @session_start();
        if (!isset($_SESSION["WA_AUTH_".$this->Name])) $_SESSION["WA_AUTH_".$this->Name] = array();
        for ($x=0; $x<sizeof($this->StoreValues); $x++) {
          if (!in_array($this->StoreValues[$x][1],$_SESSION["WA_AUTH_".$this->Name])) $_SESSION["WA_AUTH_".$this->Name][] = $this->StoreValues[$x][1];
          if ($this->StoreValues[$x][2]) {
            $_SESSION[$this->StoreValues[$x][1]] = $this->StoreValues[$x][0];
          } else {
            $_SESSION[$this->StoreValues[$x][1]] = $this->Result[$this->StoreValues[$x][0]];
          }
        }
        if (isset($_SESSION["WA_FAIL_".$this->Name])) {
          if ($this->AutoReturn) {
            $this->SuccessRedirect = $_SESSION["WA_FAIL_".$this->Name];
          }
          unset($_SESSION["WA_FAIL_".$this->Name]);
        }
        @session_commit();
        if (sizeof($this->Log) > 0) {
          for ($x=0; $x<sizeof($this->Log); $x++) {
            $table = $this->Log[$x][0];
            $columns = $this->Log[$x][1];
            $columnList = "";
            $valueList = "";
            foreach ($columns AS $key=>$value) {
              if (substr($value,0,1) =="[" && substr($value,-1) =="]") {
                $sessionRef = substr($value,2,-2);
                $columns[$key] = $_SESSION[$sessionRef];
              }
            }
            $this->LogStatement = "INSERT INTO " . $table;
            foreach ($columns AS $key=>$value) {
              $columnList .= (($columnList == "")?"":", ") . $key;
              $valueList .= (($valueList == "")?"":", ") . "?";
              $this->LogParamTypes .= 's';	
              $this->LogParamValues[] = $value;	
            }
            $this->LogStatement .= " (" . $columnList  . ") VALUES (" . $valueList . ")";
            $query = $this->Connection->Prepare($this->LogStatement);
            if ($this->LogParamTypes) call_user_func_array(array($query, "bind_param"),$this->paramRefs(array_merge(array($this->LogParamTypes), $this->LogParamValues)));
            $query->execute();
          }
        }
        $this->redirect($this->SuccessRedirect,$this->NoSuccessRedirect);
		  } else {
        if ($this->IsAuto) {
          for ($x=0; $x<sizeof($this->FilterValues); $x++) {
            $this->saveCookie("AutoLogin_".$this->FilterValues[$x][0],"");
          }
        }
        $this->logOut();
        $this->redirect($this->FailRedirect);
		  }
		  break;
		case "restrict":
		  @session_start();
		  $restrictFailed = true;
      $checkArray = is_array($this->Name)?$this->Name:array($this->Name);
		  for ($x=0; $x<sizeof($checkArray); $x++) {
			  if (isset($_SESSION["WA_AUTH_".$checkArray[$x]])) {
		      $restrictFailed = false;
			  }
		  }
		  if ($restrictFailed) {
			  for ($x=0; $x<sizeof($checkArray); $x++) {
		      $_SESSION["WA_FAIL_".$checkArray[$x]] = $_SERVER['REQUEST_URI'];
			  }
		    $this->redirect($this->FailRedirect);
		  }
		  @session_commit();
		  break;
		case "checknew":
		  if (!$this->Statement)  {
			$this->Statement = "SELECT Count(*) AS CheckNew";
			$this->Statement .= " FROM " . $this->Table;
			$this->setFilter();
			$this->Statement .= " LIMIT 1";
		  }
		  if (!$this->Prepared) {
        $statement = $this->Statement;
        if (sizeof($this->ParamValues) > 0) {
          $params = ($this->getParams());
          $paramTypes = $params[0];
          $startStatement = "";
          $endStatement = $statement;
          for ($x=0; $x<strlen($paramTypes); $x++) {
            $pos = strpos($endStatement, "?");
            if ($pos !== false) {
            if (is_null($params[$x+1])) {
              $replace = "NULL";
            } else if ($paramTypes[$x] == 'i') {
              $replace = intval($params[$x+1]);
            } else if ($paramTypes[$x] == 'd') {
              $replace = floatval($params[$x+1]);
            } else {
              $replace = "'" . mysqli_real_escape_string($this->Connection,$params[$x+1]) . "'";
            }
            $startStatement .= substr($endStatement,0,$pos) . $replace;
              $endStatement = substr($endStatement, $pos + 1);
            }
          }
          $statement = $startStatement . $endStatement;
        }
        $query = $this->Connection->query($statement);
        if ($query == false) {
          if ($this->Debug) {
            die($statement . "<BR><BR>" . mysqli_error($this->Connection));
          } else {
            die("There is an error in your SQL syntax.");
          }
        }
        if ($rows =  mysqli_fetch_assoc($query)) {
         $this->Result = $rows;
        }
		  } else {
			  $query = $this->Connection->Prepare($this->Statement);
			  if ($this->ParamTypes) call_user_func_array(array($query, "bind_param"),$this->paramRefs($this->getParams()));
			  $query->execute();
			  if (method_exists($query,'get_result')) {
				  $result = $query->get_result();
				  $this->Result = $result->fetch_array(MYSQLI_ASSOC);
			  } else {
				  $result = $this->wa_mysqli_stmt_get_result($query);
				  $this->Result = $this->wa_mysqli_result_fetch_assoc($result);
			  }
		  }
		  $query->close();
		  if ($this->Result && $this->Result['CheckNew'] > 0) {
			  if (!$this->FailRedirect) $this->FailRedirect = $this->addQuerystring($_SERVER['PHP_SELF']);
			  $this->redirect($this->FailRedirect);
		  }
		  break;
		case "logout":
		  $this->logOut();
		  break;
	  }
	}
	
	public function getParams() {
	  return array_merge(array($this->ParamTypes), $this->ParamValues); 
	}
	
	public function hasAccess() {
	  @session_start();
	  $hasAccess = true;
	  if (!isset($_SESSION["WA_AUTH_".$this->Name])) {
		$hasAccess = false;
	  }
	  @session_commit();
	  return $hasAccess;
	}
  
  public function log($table, $bindings) {
    $this->log[] = array($table, $bindings);
  }
	
	public function logOut() {
	  @session_start();
	  if (is_array($this->Name)) {
		  for ($x=0; $x<sizeof($this->Name); $x++) {
			  if (isset($_SESSION["WA_AUTH_".$this->Name[$x]])) {
				  for ($y=0; $y<sizeof($_SESSION["WA_AUTH_".$this->Name[$x]]); $y++) {
					  unset($_SESSION[$_SESSION["WA_AUTH_".$this->Name[$x]][$y]]);
				  }
			  }
			  unset($_SESSION["WA_AUTH_".$this->Name[$x]]);
		  }
	  } else {
		if (isset($_SESSION["WA_AUTH_".$this->Name])) {
			for ($y=0; $y<sizeof($_SESSION["WA_AUTH_".$this->Name]); $y++) {
				unset($_SESSION[$_SESSION["WA_AUTH_".$this->Name][$y]]);
			}
		}
		unset($_SESSION["WA_AUTH_".$this->Name]);
	  }
	  @session_commit();
	}
	
	public function paramRefs($arr) {
	  if (strnatcmp(phpversion(),'5.3') >= 0) {
		  $refs = array();
		  foreach($arr as $key => $value)
		    $refs[$key] = &$arr[$key];
		  return $refs;
	  }
	  return $arr;
	}
	
	public function redirect($url,$noRedirect=false) {
    if ($this->PassURLParameters) $this->Redirect = $this->addQuerystring($url);
	  if (!$noRedirect && $url) {
		  header("location: " . $this->Redirect);
		  die();
	  }
	}
	
	public function saveCookie($name,$val) {
		setcookie($name, $val, $this->CookieExpires, $this->CookiePath, $this->CookieDomain, $this->CookieSecure, $this->CookieHTTPOnly);
	}
	
	public function setFilter() {
	  if (sizeof($this->FilterValues) > 0) {
      $this->Statement .= " WHERE ";
      for ($x=0; $x<sizeof($this->FilterValues); $x++) {
        if ($x>0) $this->Statement .= " AND ";
        $this->Statement .= $this->FilterValues[$x][0] . " " . $this->FilterValues[$x][1] . " ?";
		    $this->bindParam($this->FilterValues[$x][2], $this->FilterValues[$x][3]);
		  }
	  }
	}
	
	public function storeResult($column,$name,$static=false) {
	  $this->StoreValues[] = array($column,$name,$static);
	}
	
	private function wa_mysqli_stmt_get_result($stmt)  {
	  $metadata = mysqli_stmt_result_metadata($stmt);
	  $ret = (object) array('nCols'=>'0', 'fields'=>array(), 'stmt'=>'');
	  $ret->nCols = mysqli_num_fields($metadata);
	  $ret->fields = mysqli_fetch_fields($metadata);
	  $ret->stmt = $stmt;
	  mysqli_free_result($metadata);
	  return $ret;
	}
	
	private function wa_mysqli_result_fetch_assoc(&$result) {
	  $ret = array();
	  $code = "mysqli_stmt_store_result(\$result->stmt); return mysqli_stmt_bind_result(\$result->stmt ";
	  for ($i=0; $i<$result->nCols; $i++) {
		  $ret[$result->fields[$i]->name] = NULL;
		  $code .= ", \$ret['" .$result->fields[$i]->name ."']";
	  };
	  $code .= ");";
	  if (!eval($code)) { return NULL; };
	  if (!mysqli_stmt_fetch($result->stmt)) { return NULL; };
	  return $ret;
	}
}
?>