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
	/* Do Work Here */
	$accountId = $_POST['accid'];
	$closeDate = $_POST['closeDate'];
	$endBalance = $_POST['endBalance'];
	
	if(trim($_POST['reconId'])) {
		//All I need to do is to update the completed field
		completeReconcile($_POST['reconId'], $endBalance, $closeDate);
	} else {
		createReconcile($accountId, $closeDate, $endBalance);		
	}
}

header("Location:./reconcile.php?id=$accountId");
?>