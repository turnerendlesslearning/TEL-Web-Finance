<?php

/*
* Copyright (C) 2011 Michael Turner <michael at turnerendlesslearning.com>
*
* This program is free software: you can redistribute it and/or modify it under
* the terms of the GNU General Public License as published by the Free Software
* Foundation, either version 3 of the License, or (at your option) any later
* version.
*
* This program is distributed in the hope that it will be useful, but WITHOUT
* ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS
* FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
*
* You should have received a copy of the GNU General Public License along with
* this program. If not, see <http://www.gnu.org/licenses/>.
*/


//Start Session
session_start();

//Includes
include_once(dirname(__FILE__) . "/includes/config.php");
include_once(dirname(__FILE__) . "/includes/security.php");
include_once(dirname(__FILE__) . "/includes/db.php");

//Security Check
if(!isLoggedIn()) {
	$_SESSION = array();
	session_destroy();
}

if(!isset($_SESSION['username'])) {
	header("Location:/index.php");
} else {
	$counter = $_POST['counter'];
	$accid = $_POST['accountid'];
	echo $counter . "<br />";
		$queries = "";
	
	
	for($i = 1; $i <= $counter; $i++) {
		$tDate = $_POST[$i . "_tDate"];
		$tCat = $_POST[$i . "_tCat"];
		$tDesc = $_POST[$i . "_tDesc"];
		$tCheck = $_POST[$i . "_tCheck"];
		$tAmount = $_POST[$i . "_tAmount"];
		$tType = 0;
		
		if(!$tCheck) {
			$tCheck = 0;
		}
		
		if($tAmount > 0) {
			$tType = 3;
		} else if($tCheck != NULL) {
			$tType = 1;
		} else {
			$tType = 2;
		}
		$transpayeeid = getPayeeId($tDesc);

		$query = "INSERT INTO trans (transdate, transnumber, transpayee, transtypeid, accid, reconid) VALUES (".
			"'$tDate', '$tCheck', '$transpayeeid', '$tType', '$accid', NULL)";
		$result = insertData($query);
		echo "DELETE FROM trans WHERE (transid = $result) LIMIT 1;<br />";
		
		if(!$result) {
			die("ERROR");
		}
		$transid = $result;
		$catid = getCategoryId($tCat);
		
		$query2 = "INSERT INTO transparts (transid, catid, amount) VALUES ('$transid',".
			"'$catid', '$tAmount')";
		$result = insertData($query2);
		echo "DELETE FROM transparts WHERE (transpartid = $result);<br />";
		
		$queries .= $query . "<br />\n" . $query2 . "<br />\n";
	}
}
echo "<br /><br />" . $queries;
die();
//header("Location:./ledger.php?id=" . $accid);
?>