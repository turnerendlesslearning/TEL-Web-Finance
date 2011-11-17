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
include_once(dirname(__FILE__) . "/includes/db.php");

//Check credentials
if(isset($_GET['u']) && isset($_GET['p']) && isset($_GET['m'])) {
	$result = checkPassword($_GET['u'], $_GET['p']);
} else {
	$result = checkPassword($_POST['username'], $_POST['password']);
}

if(is_array($result)) {
	//Successful Login
	if(isset($_GET['u']) && isset($_GET['p']) && isset($_GET['m'])) { 	
		$_SESSION['username'] = $_GET['u'];
		$_SESSION['mobile'] = true;
	} else {
		$_SESSION['username'] = $_POST['username'];
		$_SESSION['mobile'] = false;
	}
	$_SESSION['uuid'] = $result[0];
	$_SESSION['userid'] = $result[1];
} else {
	//Unsuccessful Login
	if(isset($_GET['m']) && ($_GET['m'] == true)) {
		header("Location:./index_mobile.php");
	} else {
		header("Location:./index.php");
	}
	die();
}

if(isset($_GET['m']) && ($_GET['m'] == true)) {
	header("Location:./index_mobile.php");
} else {
	header("Location:./index.php");
}
die();













$mysqli=NULL;
if(!$mysqli = db_open()) {
	die("DATABASE CONNECTION FAILED");
}

$result = $mysqli->query("SELECT username, userid, password, uniqueid FROM users WHERE (username='" . $_POST['username'] . "')");

if($result->num_rows == 0) {
	$login_error = TRUE;
	
	
} else {
	//Check the password
	$row = $result->fetch_assoc();
	
	$uniqueid = $row['uniqueid'];
	$pwhash = sha1(sha1($_POST['password']).$uniqueid);
	
	if($pwhash == $row['password']) {
		$_SESSION['username'] = $row['username'];
		$_SESSION['uuid'] = $row['uniqueid'];
		$_SESSION['userid'] = $row['userid'];
		$_SESSION['realname'] = $row['firstname'] . " " . $row['lastname'];
		$login_error = FALSE;
	} else {
		$login_error = TRUE;
	}
}


//Build the url to go back to
$loc = "http://";
$loc .= $_POST['ref_server'];
$loc .= $_POST['ref_url'];
$qs = $_POST['ref_query'];
if(strlen($qs) != 0) {
	$qsp = explode("&", $qs);
	$qs = "";
	for($i = 0; $i < count($qsp); $i++) {
		if(strpos($qsp[$i], "login_error") !== 0) {
			$qs .= $qsp[$i];
			if($i < (count($qsp) - 1)) {
				$qs .= "&";
			}
		} else {
			if($login_error) {
				$qs .= "login_error=" . LOGIN_ERROR_USERNAME_NOT_FOUND;
				if($i < (count($qsp) - 1)) {
					$qs .= "&";
				}
			}
		}
	}
	$loc .= "?".$qs;
} else {
	if($login_error) {
		$loc .= "?login_error=" . LOGIN_ERROR_USERNAME_NOT_FOUND;
	}
}

header("Location:".$loc);
?>