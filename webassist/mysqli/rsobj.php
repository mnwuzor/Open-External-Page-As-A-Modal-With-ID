<?php
// version 2.32
class WA_MySQLi_RS  {
	public function __construct($name,$conn,$maxRows=0,$skipRows=0) {
	  $this->Columns = array();
	  $this->Connection = $conn;
	  $this->CurrentPage = $_SERVER['REQUEST_URI'];
	  $this->CrossSiteProtect = true;
	  $this->Debug = false;
	  $this->EncryptionAlgorithm = "blowfish";
	  $this->EncryptionKey = "Change Me";
	  $this->EncryptionMode = "cbc";
    $this->ErrorMessage = "There is an error in your SQL syntax.";
	  $this->FilterValues = array();
	  $this->Index = 0;
	  $this->LastRow = 0;
	  $this->MaxRows = $maxRows;
	  $this->Name = $name;
	  $this->NextPage = 0;
	  $this->PageParam = 'pageNum_'.$name;
	  $this->PageNum = isset($_GET[$this->PageParam])?$_GET[$this->PageParam]:0;
	  $this->ParamTypes = array();
	  $this->ParamValues= array();
	  $this->ParamDefaults= array();
	  $this->Prepared = true;
	  $this->PrevPage = max(0, $this->PageNum - 1);
    $this->RemoveParams = array();
	  $this->Results = array();
	  $this->StartLimit = ($this->PageNum * $this->MaxRows) + $skipRows;
	  $this->SkipRows = $skipRows; 
	  $this->StartRow = 0;
	  $this->Statement = "";
	  $this->Table = "";
	  $this->TotalPages = 0;
	  $this->TotalRows = 0;
	}
	
	public function addFilter($filterColumn, $filterComparison, $filterType, $filterValue, $filterJoin = "AND") {
      $this->FilterValues[] = array($filterColumn, $filterComparison, $filterType, $filterValue);
	}
	
	public function addRow($row) {
	  $this->Results[] = $row;
	}
	
	public function atEnd() {
	  return $this->Index == sizeof($this->Results); 
	}
	
	public function bindParam($paramType,$paramValue,$paramDefault="",$paramPosition=false) {
	  if ($paramValue === 0 || $paramValue === NULL) $paramValue = $paramDefault;
	  $paramArray = array($paramValue);
	  $isList = false;
	  if (strpos($paramType,"l")) {
      $paramType = substr($paramType,0,1);
      if (is_array($paramValue)) {
        $paramArray = $paramValue;
        if (sizeof($paramArray) == 0) $paramArray[] = "";
      } else {
        $paramArray = preg_split("/\s*\,\s*/", $paramValue);
      }
      $isList = true;
	  }
	  for ($x=0; $x<sizeof($paramArray); $x++) {
		$paramValue = $paramArray[$x];
		if ($paramValue === "") {
		  switch ($paramDefault) {
			case "WA_BLANK":
			case "WA_DEFAULT":
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
		  $paramType = "s";
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
		  $paramType = "s";
		  $paramValue = "%" . $paramValue . "%";
		} else if ($paramType == "b") {
		  $paramType = "s";
		  $paramValue = $paramValue . "%";
		} else if ($paramType == "e") {
		  $paramType = "s";
		  $paramValue = "%" . $paramValue;
		} 
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
	
	public function debugSQL() {
	  $debugStatement = $this->Statement;
	  $startStatement = "";
	  $endStatement = $debugStatement;
      $params = ($this->getParams());
	  $paramTypes = $params[0];
	  for ($x=0; $x<strlen($paramTypes); $x++) {
		  $pos = strpos($endStatement, "?");
		  if ($pos !== false) {
        $replace = $this->getSQLValue($params[$x+1],$paramTypes[$x],true);
			  $startStatement .= substr($endStatement,0,$pos) . $replace;
			  $endStatement = substr($endStatement, $pos + 1);
		  }
	  }
	  $debugStatement = $startStatement . $endStatement;
    
	  if ($this->MaxRows && !preg_match("/ LIMIT \d*(\s*(\,|OFFSET)\s*\d*)?$/i",$debugStatement)) {
      $debugStatement .= " LIMIT ".($this->StartLimit?$this->StartLimit.",":"").$this->MaxRows;
    } else if ($this->SkipRows && !preg_match("/ LIMIT \d*(\s*(\,|OFFSET)\s*\d*)?$/i",$debugStatement)) {
      $debugStatement .= " OFFSET ". $this->SkipRows;
    }
	  return $debugStatement;
	}
	
	public function execute() {
	  if (!$this->Statement && $this->Table) {
		 $this->Statement = "SELECT * FROM " . $this->Table; 
	  }
	  $this->setFilter();
	  $this->setPagination();
	  $this->setSort();
	  $this->setSearch();
	  $statement = $this->Statement;
	  if ($this->MaxRows && !isset($_GET['totalRows_'.$this->Name])) {
	    $statement = preg_replace('/^select /i',"SELECT SQL_CALC_FOUND_ROWS ",$statement);
	  }
	  if ($this->MaxRows && !preg_match("/ LIMIT \d*(\s*(\,|OFFSET)\s*\d*)?$/i",$statement)) {
      $statement .= " LIMIT ".($this->StartLimit?$this->StartLimit.",":"").$this->MaxRows;
    } else if ($this->SkipRows && !preg_match("/ LIMIT \d*(\s*(\,|OFFSET)\s*\d*)?$/i",$statement)) {
      $statement .= " OFFSET ". $this->SkipRows;
    }
	  if (!$this->Prepared) {
		  if (sizeof($this->ParamValues) > 0) {
			  $params = ($this->getParams());
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
		  $query = $this->Connection->query($statement);
		  if ($query == false) {
        $this->showError($statement . "<BR><BR>" . mysqli_error($this->Connection));
	    }
      $this->Columns = mysqli_fetch_fields($query);
		  while ($rows =  mysqli_fetch_assoc($query)) {
		   $this->addRow($rows);
		  }
	  } else {
		  if (sizeof($this->ParamValues) > 0) {
			  $query = $this->Connection->Prepare($statement);
			  if ($query == false) {
          $this->showError($this->Statement . "<BR><BR>" . mysqli_error($this->Connection));
			  }
			  $rsParams = $this->getParams();
			  if ($rsParams[0]) call_user_func_array(array($query, "bind_param"),$this->paramRefs($rsParams));
			  $query->execute();
	      $result= $query->result_metadata();
	      $this->Columns = ($result->fetch_fields());
		  } else {
			  $query = $this->Connection->query($statement); 
		    if ($query == false) {
          $this->showError($this->Statement . "<BR><BR>" . mysqli_error($this->Connection));
			  }
        $this->Columns = mysqli_fetch_fields($query);
		  }
		  if (method_exists($query,'fetch_assoc')) {
			  while ($rows = $query->fetch_assoc()) {
			    $this->addRow($rows);
			  }
		  }  else if (method_exists($query,'get_result')) {
        $result = $query->get_result();
        if ($result == false) {
          $this->showError($this->Statement . "<BR><BR>" . $query->error . "<BR><BR>" . mysqli_error($this->Connection));
        }
        while ($rows = $result->fetch_array(MYSQLI_ASSOC)) {
          $this->addRow($rows);
        }
		  } else {
        $result = $this->waMySQLiStmtGetResult($query);
        while ($rows = $this->waMySQLiResultFetchAssoc($result)) {
          $this->addRow($rows);
        }
		  }
	  }

	  if ($this->MaxRows) {
      if (isset($_GET['totalRows_'.$this->Name])) {
        $this->TotalRows = intval($_GET['totalRows_'.$this->Name]);
      } else {
        $stmt = $this->Connection->query("SELECT FOUND_ROWS() AS TotalRows");
        $totalRows =  mysqli_fetch_assoc($stmt);
        $this->TotalRows = intval($totalRows["TotalRows"]-$this->SkipRows);
        if ($this->TotalRows < 0) $this->TotalRows = 0;
      }
      $this->TotalPages = ceil(($this->TotalRows)/$this->MaxRows)-1;
	  } else {
      $this->TotalRows = sizeof($this->Results);
	  }
	  if ($this->TotalRows < $this->StartRow) {
		  $this->StartRow = $this->TotalRows;
	  } else if ($this->TotalRows > 0) {
		  $this->StartRow = ($this->PageNum * $this->MaxRows) + 1;
	  }
	  $this->LastRow = min($this->StartRow + $this->MaxRows - 1, $this->TotalRows);
	  if ($this->MaxRows == 0) $this->LastRow = $this->TotalRows;
	  $this->NextPage = min($this->TotalPages, $this->PageNum + 1);
    if ($query !== true && $query !== false) $query->close();
	}
	
	public function findRow($column,$value) {
	  for ($x=0; $x<sizeof($this->Results); $x++) {
      if ($this->Results[$x][$column] == $value) {
        $this->Index = $x;
        return true;
      }
	  }
	  return false;
	}
	
	public function getColumnArray($col) {
	  $colArray = array();
	  for ($x=0; $x<sizeof($this->Results); $x++) {
		  $colArray[] = $this->Results[$x][$col];
	  }
	  return $colArray;
	}
	
	public function getColumns() {
	  $colArray = array();
	  for ($x=0; $x<sizeof($this->Columns); $x++) {
		$colArray[] = $this->Columns[$x]->name;
	  }
	  return $colArray;
	}
	
	public function getColumnVal($col,$crossSiteProtect=null) {
	  if (is_null($crossSiteProtect)) $crossSiteProtect = $this->CrossSiteProtect;
	  $colVal = "";
	  if (isset($this->Results[$this->Index]) && isset($this->Results[$this->Index][$col])) {
		  $colVal = $this->Results[$this->Index][$col];
		  if ($crossSiteProtect) {
        $colVal = str_replace('"','&quot;',$colVal);
        $colVal = str_replace('<','&lt;',$colVal);
        $colVal = str_replace('>','&gt;',$colVal);
        $colVal = str_replace("'",'&#x27;',$colVal);
        $colVal = str_replace('/','&#x2F;',$colVal);
      }
	  }
	  return strval($colVal);
	}

  public function getDecryptedVal($col, $salt="") {
    $colVal = $this->getColumnVal($col);
    if ($colVal) $colVal = openssl_decrypt(base64_decode($colVal), $this->EncryptionAlgorithm, $this->EncryptionKey, OPENSSL_RAW_DATA, base64_decode($salt));
    return trim($colVal, "\0");
  }

	
	public function getFirstPageLink() {
    return $this->getPageLink(0);
	}
	
	public function getLastPageLink() {
    return $this->getPageLink($this->TotalPages);
	}
	
	public function getNextPageLink() {
    return $this->getPageLink($this->NextPage);
	}
	
	public function getPageLink($page) {
    $startPage = $this->CurrentPage;
    $urlParams = array();
    if (strpos($startPage,"?") !== false) {
      $pageParams = explode("?",$startPage);
      $startPage = $pageParams[0];
      $urlParams = explode("&",str_replace("&amp;","&",$pageParams[1]));
      $this->RemoveParams[] = $this->PageParam;
      $this->RemoveParams[] = "totalRows_" . $this->Name;
      for ($x=sizeof($urlParams)-1; $x>=0; $x--) {
        $urlPieces = explode("=",$urlParams[$x]);
        if (in_array($urlPieces[0],$this->RemoveParams)) {
          unset($urlParams[$x]);
        }
      }
    }
	  return $startPage . "?" . $this->PageParam . "=" . $page . "&totalRows_" . $this->Name . "=" . $this->TotalRows . (sizeof($urlParams)>0?"&".implode("&",$urlParams):"");
	}
	
	private function getParams() {
	  $useParams = array();
	  $useTypes = array();
	  for ($x=0; $x<sizeof($this->ParamValues); $x++) {
      $paramVal = $this->ParamValues[$x];
      if (!(($paramVal === "" || $paramVal === null) && ($this->ParamDefaults[$x] == "WA_DEFAULT" || $this->ParamDefaults[$x] == "WA_IGNORE"))) {
        $useParams[] = $paramVal;
        $useTypes[] = $this->ParamTypes[$x];
      }
	  }
	  return array_merge(array(implode("",$useTypes)), $useParams);
	}
	
	public function getPrevPageLink() {	  	
    return $this->getPageLink($this->PrevPage);
	}
	
	public function getRow() {	  	
    if ($this->atEnd()) return false;
    return $this->Results[$this->Index];
	}
	
	private function getSQLPiece($thePiece) {
	  $pieces = array("SELECT","FROM","WHERE","GROUP BY","HAVING","ORDER BY","HAVING","LIMIT");
	  $sqlBefore = "";
	  $sqlMatch = "";
	  $sqlAfter = $this->Statement;
	  $searchAfter = $sqlAfter;
	  $endSearch = false;
	  for ($x=0; $x<sizeof($pieces); $x++) {
      $pattern = "/[\s\(]".(($x!=0)?"":"?").$pieces[$x]."[\s\)].*/i";
      $allMatches = array();
      $allSearch = $searchAfter;
      while ($match = preg_match($pattern,$allSearch,$found)) {
        $allMatches = array_merge($allMatches, $found);
        $allSearch = substr($allSearch, strpos($allSearch,trim($found[0]))+1);
      }
      if (sizeof($allMatches)>0) {
        $matchIndex = 0;
        if ($pieces[$x] != "SELECT") $matchIndex = sizeof($allMatches)-1;
        if ($endSearch === false || $x < $endSearch) {
          $sqlBefore .= substr($sqlAfter,0,strpos($sqlAfter,trim($allMatches[$matchIndex])));
          $sqlAfter = substr($sqlAfter,strpos($sqlAfter,trim($allMatches[$matchIndex])));
        }
        $searchAfter =  substr($sqlAfter,strpos($sqlAfter,trim($allMatches[$matchIndex])));
        if ($thePiece == $pieces[$x]) {
          $sqlMatch = $sqlAfter;
          $endSearch = $x;
        }
        if ($endSearch!== false && $endSearch < $x) {
          if ($sqlMatch) {
            $sqlAfter = substr($sqlMatch,strpos($sqlMatch,$allMatches[$matchIndex]));
            $sqlMatch = substr($sqlMatch,0,strpos($sqlMatch,$allMatches[$matchIndex]));
          } else {
            $sqlBefore .= substr($sqlAfter,0,strpos($sqlAfter,trim($allMatches[$matchIndex])));
            $sqlAfter = substr($sqlAfter,strpos($sqlAfter,trim($allMatches[$matchIndex])));
          }
          return array($sqlBefore,$sqlMatch,$sqlAfter);
        }
      } else {
        if ($thePiece == $pieces[$x]) {
          $endSearch = $x;
        }
      }
	  }
	  if ($endSearch !== false) {
      if ($sqlMatch) {
        $sqlAfter = ""; 
      } else {
        $sqlBefore .= $sqlAfter;
        $sqlAfter = ""; 
      }
	  }
	  return array($sqlBefore,$sqlMatch,$sqlAfter);
	}
  
  public function getSQLValue($val,$type,$forDebug=false) {
    if (is_null($val)) {
      $retval = "NULL";
    } else if ($type == 'i') {
      if (!$forDebug && strval(intval($val)) != $val) {
        $this->showError($this->debugSQL() . "<BR><BR>" . "Truncated incorrect INTEGER value: " . $val);
      }
      $retval = intval($val);
    } else if ($type == 'd') {
      if (!$forDebug && strval(floatval($val)) != $val) {
        $this->showError($this->debugSQL() . "<BR><BR>" . "Truncated incorrect DOUBLE value: " . $val);
      }
      $retval = floatval($val);
    } else {
      $retval = "'" . mysqli_real_escape_string($this->Connection,$val) . "'";
    }
    return $retval;
  }
	
	public function inResult($col,$val) {
	  for ($x=0; $x<sizeof($this->Results); $x++) {
		if ($this->Results[$x][$col] == $val) return true;
	  }
	  return false;
	}
	
	public function moveFirst() {
	  $this->Index = 0;
	}
	
	public function moveNext() {
	  $this->Index++;
	  if (sizeof($this->Results) > $this->Index) {
	    return true;
	  }
	  $this->Index = sizeof($this->Results);
	  return false;
	}
	
	public function movePrevious() {
	  if ($this->Index>0) {
	    $this->Index--;
	    return true;
	  }
	  return false;
	}
	
	public function paramRefs($arr) {
	  if (strnatcmp(phpversion(),'5.3') >= 0)
	  {
		$refs = array();
		foreach($arr as $key => $value)
		  $refs[$key] = &$arr[$key];
		return $refs;
	  }
	  return $arr;
	}
	
	public function setFilter() {
	  if (sizeof($this->FilterValues) > 0) {
		$addFilter = "";
		$pieces = $this->getSQLPiece("WHERE");
        for ($x=0; $x<sizeof($this->FilterValues); $x++) {
		  if (!is_array($this->FilterValues[$x][3])) {
			$filterValues = array($this->FilterValues[$x][3]);
		  } else {
			$filterValues = $this->FilterValues[$x][3];
		  }
		  for ($y=0; $y<sizeof($filterValues); $y++) {
			  if ($x>0 && $y==0) $addFilter .= " AND ";
		    if (sizeof($filterValues)>1 && $y==0) $addFilter .= "(";
			  if ($y>0) $addFilter .= ($this->FilterValues[$x][1] == "<>" || strtoupper($this->FilterValues[$x][1]) == "IS NOT")?" AND ":" OR ";
			  if (sizeof($this->FilterValues[$x]) > 4) $this->RepeatedParams[] = array(sizeof($this->ParamValues),$this->FilterValues[$x][4],true);
        if (strtoupper($this->FilterValues[$x][1]) == "IS NOT" || strtoupper($this->FilterValues[$x][1]) == "IS") {
           $addFilter .= $this->FilterValues[$x][0] . " " . $this->FilterValues[$x][1] . " NULL";
        } else {
			    $addFilter .= $this->FilterValues[$x][0] . " " . $this->FilterValues[$x][1] . " ?";
			    $this->bindParam($this->FilterValues[$x][2], strval($filterValues[$y]), "");
        }
		  }
		  if (sizeof($filterValues)>1) $addFilter .= ")";
		}
      $this->Statement = $pieces[0] . ($pieces[1]? $pieces[1] . " AND ":" WHERE ") . $addFilter . $pieces[2];
	  }
	}
	
	public function setQuery($statement) {
	  $this->Statement = $statement;	
	}
  
  public function setPagination() {
	  $this->PageNum = isset($_GET[$this->PageParam])?$_GET[$this->PageParam]:0;
	  $this->PrevPage = max(0, $this->PageNum - 1);
    $this->NextPage = min($this->TotalPages, $this->PageNum + 1);
	  $this->StartLimit = ($this->PageNum * $this->MaxRows) + $this->SkipRows;
  }
	
	public function setSearch() {
	  $search = "";
	  if (isset($GLOBALS['WA_RSSearch'])) {
		  for ($x=sizeof($GLOBALS['WA_RSSearch'])-1; $x>=0; $x--) {
		    if ($GLOBALS['WA_RSSearch'][$x]->Recordset == $this->Name) {
			    $searchVals = $GLOBALS['WA_RSSearch'][$x]->getSearch();
			    $addSearch = $searchVals[0];
		  	  if ($addSearch) {
		        $pieces = $this->getSQLPiece("WHERE");
			      $paramPos = sizeof($this->ParamValues) - substr_count($pieces[2],"?");
			      for ($y=0; $y<sizeof($searchVals[1]); $y++) {
			        $this->bindParam($searchVals[1][$y][0],$searchVals[1][$y][1],"",$paramPos+$y);
			      }
            $this->Statement = $pieces[0] . ($pieces[1]? $pieces[1] . " AND ":" WHERE ") . $addSearch . " " . $pieces[2];
			    }
		    }
		  }
	  }
	  if ($search) {
      $pieces = $this->getSQLPiece("WHERE");
	    $this->Statement = trim($pieces[0]) . " WHERE " . $search . " " . trim($pieces[2]);
	  }
	}
	
	public function setSort() {
	  $sort = "";
	  if (isset($GLOBALS['WA_RSSorts'])) {
		  for ($x=sizeof($GLOBALS['WA_RSSorts'])-1; $x>=0; $x--) {
		    if ($GLOBALS['WA_RSSorts'][$x]->Recordset == $this->Name) {
			  $sort = $GLOBALS['WA_RSSorts'][$x]->getSort();
		      break;
		    }
		  }
	  }
	  if ($sort) {
		  $pieces = $this->getSQLPiece("ORDER BY");
      $existingOrder = "";
      if (trim($pieces[1])) {
        $existingOrder = "," . substr(trim($pieces[1]),8);
      }
	    $this->Statement = trim($pieces[0]) . " ORDER BY " . $sort . $existingOrder . " " . trim($pieces[2]);
	  }
	}
	
	private function showError($errorMessage) {
    if ($this->Debug) {
      die($errorMessage);
    } else {
      die($this->ErrorMessage);
    }
	}
	
	private function waMySQLiResultFetchAssoc(&$result) {
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
	
	private function waMySQLiStmtGetResult($stmt)  {
	  $metadata = mysqli_stmt_result_metadata($stmt);
	  $ret = (object) array('nCols'=>'0', 'fields'=>array(), 'stmt'=>'');
	  $ret->nCols = mysqli_num_fields($metadata);
	  $ret->fields = mysqli_fetch_fields($metadata);
	  $ret->stmt = $stmt;
	  mysqli_free_result($metadata);
	  return $ret;
	}
	
}

class WA_MySQLi_Sort  {
	public function __construct($recordset,$default="",$allowMultiple=false) {
	  if (!isset($GLOBALS['WA_RSSorts'])) $GLOBALS['WA_RSSorts'] = array();
	  $GLOBALS['WA_RSSorts'][] = $this;
	  $this->AllowMultiple = $allowMultiple;
	  $this->Default = $default;
	  $this->Recordset = $recordset;
	  $this->SortArray = isset($_SESSION["WASort_".$this->Recordset])?$_SESSION["WASort_".$this->Recordset]:array();
	  $this->SortOrder = array();
	}
	
	public function activeSort($col) {
	  $sort = "";
	  for ($x=0; $x<sizeof($this->SortArray); $x++) {
		if ($this->SortArray[$x][0] == $col) $sort = $this->SortArray[$x][1];
	  }
	  return strtoupper($sort);
	}
	
	public function clearSort() {
	  @session_start();
	  $this->SortArray = array();
	  unset($_SESSION["WASort_".$this->Recordset]);
	  session_commit();
	}
	
	public function getSort() {
	  if (!$this->AllowMultiple && sizeof($this->SortOrder) > 0) {
	    for ($x=sizeof($this->SortArray)-1; $x>=0; $x--) {	
		  if (!in_array($this->SortArray[$x][0], $this->SortOrder)) {
			  array_splice($this->SortArray, $x, 1);
		  }
		}
	  }
	  $newSort = array();
	  for ($x=0; $x<sizeof($this->SortOrder); $x++) {
	    for ($y=sizeof($this->SortArray)-1; $y>=0; $y--) {
		  if ($this->SortArray[$y][0] == $this->SortOrder[$x]) {
			$newSort[] = $this->SortArray[$y];
			array_splice($this->SortArray, $y, 1);
		    break;
		  }
		}
	  }
	  for ($y=0; $y<sizeof($this->SortArray); $y++) {
	    $newSort[] = $this->SortArray[$y];
	  }
	  $this->SortArray = $newSort;
	
	  $sort = "";
	  for ($x=0; $x<sizeof($this->SortArray); $x++) {
		if ($x!=0) $sort .= ",";
		$sort .= ' ' . $this->SortArray[$x][0] . " " . $this->SortArray[$x][1];
	  }
	  if (!$sort) {
		 $sort = $this->Default;
		 if (strpos(strtoupper($sort),"ORDER BY ") !== false) {
			$sort = substr($sort,strpos(strtoupper($sort),"ORDER BY ")+9); 
		 }
	  }
	  return $sort;
	}
	
	public function saveInSession() {
	  @session_start();
	  $_SESSION["WASort_".$this->Recordset] = $this->SortArray;
	  session_commit();
	}
	
	public function setSort($column,$direction="ASC",$toggle=true,$clear=true) {
	  if ($column == "") {
		  $this->clearSort();
		  return;
	  }
	  if (in_array($column,$this->SortOrder)) return;
	  $this->SortOrder[] = $column;
	  $direction = strtoupper($direction);
	  $found = false;
	  $sortArray = $this->SortArray;
	  for ($x=0; $x<sizeof($sortArray); $x++) {
		if ($sortArray[$x][0] == $column) {
		  $found = true;
		  if (strtoupper($sortArray[$x][1]) == $direction) {
			if ($toggle == true) {
			  array_splice($this->SortArray,$x,1);
		      array_unshift($this->SortArray, array($column,($direction=="ASC")?"DESC":"ASC"));
			} else {
			  if ($clear == true) {
				array_splice($this->SortArray,$x,1);
			  }
			}
		  } else {
			if ($clear == true) {
			  array_splice($this->SortArray,$x,1);
			} else {
			  array_splice($this->SortArray,$x,1);
		      array_unshift($this->SortArray, array($column,$direction));
			}
		  }
		} else if (!$this->AllowMultiple) {
		  array_splice($this->SortArray,$x,1);
		}
	  }
	  if (!$found) {
		array_unshift($this->SortArray, array($column,$direction));
	  }
	  $this->saveInSession();
	}
}

class WA_MySQLi_Search  {
	public function __construct($recordset,$default="") {
	  if (!isset($GLOBALS['WA_RSSearch'])) $GLOBALS['WA_RSSearch'] = array();
	  $GLOBALS['WA_RSSearch'][] = $this;
	  $this->Recordset = $recordset;
	  $this->Default = $default;
	  $this->SearchArray = isset($_SESSION["WASearch_".$this->Recordset])?$_SESSION["WASearch_".$this->Recordset]:array();
	  $this->SearchGroup = array();
	}
	
	public function clearSearch() {
	  @session_start();
	  $this->SearchArray = array();
	  unset($_SESSION["WASearch_".$this->Recordset]);
	  session_commit();
	}
	
	private function escapeColumnName($col) {
		if (strpos($col,"(") === false && strpos($col,"`") === false) {
			$col = "`".str_replace(".","`.`",$col)."`";
		}
		return $col;
	}
	
	public function getColumnFilter($col,$comp=false) {
	  for ($x=0; $x<sizeof($this->SearchArray); $x++) {
	    for ($y=0; $y<sizeof($this->SearchArray[$x]); $y++) {
	      for ($z=0; $z<sizeof($this->SearchArray[$x][$y][1]); $z++) {
          if ($this->SearchArray[$x][$y][1][$z] == $col && $this->SearchArray[$x][$y][3] && (!$comp || $this->SearchArray[$x][$y][0]["comparison"] == $comp)) {
            return $this->SearchArray[$x][$y][3];
          }
        }
      }
    }
    return "";
	}
	
	public function getSearch() {
	  $searchStr = "";
	  $searchParams = array();
	  if (sizeof($this->SearchGroup) > 0) {
	    array_push($this->SearchArray, $this->SearchGroup);
	  }
	  for ($w=0; $w<sizeof($this->SearchArray); $w++) {
      $groupSearchEmpty = true;
      $groupSearchOpen = false;
		  for ($x=0; $x<sizeof($this->SearchArray[$w]); $x++) {
        $searchType = strtolower((isset($this->SearchArray[$w][$x][0]["type"]) && $this->SearchArray[$w][$x][0]["type"])?$this->SearchArray[$w][$x][0]["type"]:"v");
        $searchColType = $this->SearchArray[$w][$x][2];
        $searchJoin = strtolower((isset($this->SearchArray[$w][$x][0]["join"]) && $this->SearchArray[$w][$x][0]["join"])?$this->SearchArray[$w][$x][0]["join"]:"AND");
        $searchValues = (isset($this->SearchArray[$w][$x][3]))?$this->SearchArray[$w][$x][3]:"";
        $searchComparison = (isset($this->SearchArray[$w][$x][0]["comparison"]) && $this->SearchArray[$w][$x][0]["comparison"])?$this->SearchArray[$w][$x][0]["comparison"]:"=";
        $searchColumns = $this->SearchArray[$w][$x][1];
        if (!is_array($searchValues)) $searchValues = array($searchValues);
        $newGroup = true;
        for ($s=0; $s<sizeof($searchValues); $s++) {
          $searchValue = $searchValues[$s];
          $useComparison = $searchComparison;
          $useColType = $searchColType;
          if (strpos($searchType ,"e") === 0 || strpos($searchType ,"l") === 0) {
            if (isset($_POST[$searchValue])) {
              $searchValue = $_POST[$searchValue];
            } else if (isset($_GET[$searchValue])) {
              $searchValue = $_GET[$searchValue];
            } else {
              $searchValue = "";	
            }
            $this->SearchArray[$w][$x][3] = $searchValue;
            $this->SearchArray[$w][$x][0]["type"] = "v";
          }
          if (strpos($searchType ,"c") === 0) {
            if (isset($_POST[$searchValue]) || isset($_GET[$searchValue])) {
              if (isset($_POST[$searchValue])) {
                $searchValue = $_POST[$searchValue]; 
              } else if (isset($_POST[$searchValue])) $searchValue = $_GET[$searchValue];
              if (isset($this->SearchArray[$w][$x][0]["checked_value"])) $searchValue = $this->SearchArray[$w][$x][0]["checked_value"];
            } else {
              if (isset($this->SearchArray[$w][$x][0]["unchecked_value"])) {
                $searchValue = $this->SearchArray[$w][$x][0]["unchecked_value"];
              } else {
                $searchValue = "";
              }
              if (isset($this->SearchArray[$w][$x][0]["unchecked_comparison"])) $useComparison = $this->SearchArray[$w][$x][0]["unchecked_comparison"];
              $this->SearchArray[$w][$x][3] = $searchValue;
              $this->SearchArray[$w][$x][0]["type"] = "v";
              $this->SearchArray[$w][$x][0]["comparison"] = $useComparison;
            }
          }
          
          if (strtolower($useComparison) != "is" && strtolower($useComparison) != "is not" ) {
            if (substr(strtolower($useComparison),0,1) == "i") {
              $useComparison = "LIKE";
              $useColType = "c";
            } else if (substr(strtolower($useComparison),0,1) == "b") {
              $useComparison = "LIKE";
              $useColType = "b";
            } else if (substr(strtolower($useComparison),0,1) == "e") {
              $useComparison = "LIKE";
              $useColType = "e";
            }
          }
          if (is_array($searchValue)) {
            $hasSearch = false;
            for ($y=0; $y<sizeof($searchValue); $y++) {
              if ($searchValue[$y] !== "") {
                $hasSearch = true;
                break; 
              }
            }
          } else {
            $hasSearch = ($searchValue !== "");
            if (strpos($searchType ,"k") === 0) {
              $searchCheck = str_replace($this->SearchArray[$w][$x][0]["and"],"",$searchValue);
              $searchCheck = str_replace($this->SearchArray[$w][$x][0]["or"],"",$searchCheck);
              $hasSearch = ($searchCheck !== "");
            }
          }
          if ($hasSearch) {
            if ($newGroup == true) {
              if (sizeof($searchValues) > 1) {
                if ($searchStr !== "" && !$groupSearchEmpty) {
                  $searchStr .= " " . $searchJoin . " (";
                } else {
                  $searchStr .= "(";
                }
              } else {
                if ($searchStr !== "") {
                  if ($groupSearchEmpty) $groupSearchOpen = true;
                  $searchStr .= " " . ($groupSearchEmpty?" ".$searchJoin." (":$searchJoin . " ");
                }
              }	
              $newGroup = false;
            } else {
              if (strlen($searchStr) > 4 && substr($searchStr, -4) != " OR ")  $searchStr .= " OR ";
            }
            if (strpos($searchType ,"k") === 0) {
              $valueArray = array();
              $valueArray[] = array($searchValue,"a");
              $keyAnd = ((isset($this->SearchArray[$w][$x][0]["and"]) && $this->SearchArray[$w][$x][0]["and"])?$this->SearchArray[$w][$x][0]["and"]:"");
              $keyOr = ((isset($this->SearchArray[$w][$x][0]["or"]) && $this->SearchArray[$w][$x][0]["or"])?$this->SearchArray[$w][$x][0]["or"]:"");
              $keyStartEnc = ((isset($this->SearchArray[$w][$x][0]["start_encap"]) && $this->SearchArray[$w][$x][0]["start_encap"])?$this->SearchArray[$w][$x][0]["start_encap"]:"");
              $keyEndEnc = ((isset($this->SearchArray[$w][$x][0]["end_encap"]) && $this->SearchArray[$w][$x][0]["or"])?$this->SearchArray[$w][$x][0]["end_encap"]:"");
              if ($keyStartEnc !== "" && $keyEndEnc !== "") {
                for ($z=0; $z<sizeof($valueArray); $z++) {
                  $thisVal = $valueArray[$z][0];
                  if ($thisVal === "") continue;
                  if (strpos($thisVal,$keyStartEnc) !== false && strpos($thisVal,$keyEndEnc) !== false) {
                    $beforeStart = substr($thisVal,0,strpos($thisVal,$keyStartEnc));
                    $afterStart = substr($thisVal,strpos($thisVal,$keyStartEnc)+strlen($keyStartEnc));
                    if (strpos($afterStart,$keyEndEnc) !== false) {
                      $exactMatch = substr($afterStart,0,strpos($afterStart,$keyEndEnc));
                      $afterStart = substr($afterStart,strpos($afterStart,$keyEndEnc)+strlen($keyEndEnc));
                      if ($beforeStart) {
                        $valueArray[$z][0] = $beforeStart;
                        $valueArray[] = array($exactMatch,"e");
                      } else {
                        $valueArray[$z][0] = $exactMatch;
                        $valueArray[$z][1] = "e";
                      }
                      if ($afterStart) $valueArray[] = array($afterStart,"a");
                      $z++;
                    }
                  }
                }
              }
              $orFirst = false;
              if ($keyAnd !== "") {
                for ($z=0; $z<sizeof($valueArray); $z++) {
                  if (strpos($keyOr,$keyAnd) !== false) {
                    $orFirst = true;
                    break;
                  }
                  if ($valueArray[$z][1] == "e") continue;
                  $thisVal = $valueArray[$z][0];
                  if (strpos($thisVal,$keyAnd) !== false) {
                    $beforeAnd = substr($thisVal,0,strpos($thisVal,$keyAnd));
                    $afterAnd = substr($thisVal,strpos($thisVal,$keyAnd)+strlen($keyAnd));
                    $valueArray[$z][0] = $beforeAnd;
                    array_splice($valueArray, $z+1, 0, array(array($afterAnd,"a")));
                  }
                }
              }
              if ($keyOr !== "") {
                for ($z=0; $z<sizeof($valueArray); $z++) {
                  if ($valueArray[$z][1] == "e") continue;
                  $thisVal = $valueArray[$z][0];
                  if (strpos($thisVal,$keyOr) !== false) {
                    $beforeOr = substr($thisVal,0,strpos($thisVal,$keyOr));
                    $afterOr = substr($thisVal,strpos($thisVal,$keyOr)+strlen($keyOr));
                    $valueArray[$z][0] = $beforeOr;
                    array_splice($valueArray, $z+1, 0, array(array($afterOr,"o")));
                  }
                }
              }
              if ($keyAnd !== "" && $orFirst == true) {
                for ($z=0; $z<sizeof($valueArray); $z++) {
                  if ($valueArray[$z][1] == "e") continue;
                  $thisVal = $valueArray[$z][0];
                  if (strpos($thisVal,$keyAnd) !== false) {
                    $beforeAnd = substr($thisVal,0,strpos($thisVal,$keyAnd));
                    $afterAnd = substr($thisVal,strpos($thisVal,$keyAnd)+strlen($keyAnd));
                    $valueArray[$z][0] = $beforeAnd;
                    array_splice($valueArray, $z+1, 0, array(array($afterAnd,"a")));
                  }
                }
              }
              $searchValue = $valueArray;
            }
            $searchStr .= "(";
            for ($y=0; $y<sizeof($searchColumns); $y++) {
              if ($y!=0) $searchStr .= " OR ";
              if (strtolower($useComparison) == "is" || strtolower($useComparison) == "is not" ) {
                $searchStr .= $this->escapeColumnName($searchColumns[$y]). " " . $useComparison . " NULL";
              } else {
                if (is_array($searchValue)) {
                  $searchStr .= "(";
                  $oneAdded = false;
                  for ($z=0; $z<sizeof($searchValue); $z++) {
                    if (is_array($searchValue[$z])) {
                      $strValue = $searchValue[$z][0];
                      $strJoin = ($searchValue[$z][1] == "a")?" AND ":" OR ";
                    } else {
                      $strValue = $searchValue[$z];
                      $strJoin = " OR ";
                    }
                    if ($strValue === "") continue;
                    if ($oneAdded) $searchStr .= $strJoin;
                    $oneAdded = true;
                    if ($useColType == "t") {
                      $searchStr .= "date(".$this->escapeColumnName($searchColumns[$y]).")" . " " . $useComparison . " ?";
                      $hasTime = strpos($strValue," ") !== false;
                      if (!$hasTime) {
                        if (strpos($useComparison,"<")!==false && strpos($useComparison,">")===false) {
                          $strValue .= " 23:59:59";
                        } else if (strpos($useComparison,">")!==false && strpos($useComparison,"<")===false) {
                          $strValue .= " 00:00:00";
                        }
                      }
                    } else {
                      $searchStr .= $this->escapeColumnName($searchColumns[$y]). " " . $useComparison . " ?";
                    }
                    if ($strValue == "WA_BLANK") $strValue = "";
                    $searchParams[] = array($useColType,$strValue);
                  }
                  $searchStr .= ")";
                } else {
                  if ($useColType == "t") {
                    $searchStr .= "date(".$this->escapeColumnName($searchColumns[$y]).")" . " " . $useComparison . " ?";
                    $hasTime = strpos($searchValue," ") !== false;
                    if (!$hasTime) {
                      if (strpos($useComparison,"<")!==false && strpos($useComparison,">")===false) {
                        $searchValue .= " 23:59:59";
                      } else if (strpos($useComparison,">")!==false && strpos($useComparison,"<")===false) {
                        $searchValue .= " 00:00:00";
                      }
                    }
                  } else {
                    $searchStr .= $this->escapeColumnName($searchColumns[$y]) . " " . $useComparison . " ?";
                  }
                  if ($searchValue == "WA_BLANK") $searchValue = "";
                  $searchParams[] = array($useColType,$searchValue);
                }
              }
            }
            $searchStr .= ")";
            $groupSearchEmpty = false;
          }
        }
        if ($newGroup == false && sizeof($searchValues) > 1) $searchStr .= ")";
		  }
		  if ($groupSearchOpen) $searchStr .= ")";
		  if (!$groupSearchEmpty) $searchStr = "(" .$searchStr .")";
	  }
	  if (!$searchStr) $searchStr = $this->Default;
	  @session_start();
	  $_SESSION["WASearch_".$this->Recordset] = $this->SearchArray;
	  @session_commit();
	  return array($searchStr,$searchParams);
	}
	
	public function newGroup() {
	  array_push($this->SearchArray, $this->SearchGroup);
	  $this->SearchGroup = array();
	}
	
	public function saveInSession() {
	  @session_start();
	  $retArray = $this->SearchArray;
	  array_push($retArray, $this->SearchGroup);
	  $_SESSION["WASearch_".$this->Recordset] = $retArray;
	  @session_commit();
	}
	
	public function setSearch($options,$columns,$datatype,$value,$repeat=false) {
	  $searchType = strtolower((isset($options["type"]) && $options["type"])?$options["type"]:"v");
	  if ($repeat)  {
		$value = array();
		$repeatIndex = 1;
		while (isset($_POST['WA_RepeatID' . $repeatIndex]) || isset($_GET['WA_RepeatID' . $repeatIndex])) {
		  if (strpos($searchType ,"e") === 0 || strpos($searchType ,"l") === 0 || strpos($searchType ,"c") === 0) $searchValues[] = $repeat.$repeatIndex;
		  else if (isset($_POST['WA_RepeatID' . $repeatIndex])) {
			$value[] = $_POST[$repeat.$repeatIndex];
		  } else {
			$value[] = $_GET[$repeat.$repeatIndex];
		  }
		  $repeatIndex++;
		}
	  }
	  array_push($this->SearchGroup, array($options,$columns,$datatype,$value));
	  $this->saveInSession();
	}
}
?>