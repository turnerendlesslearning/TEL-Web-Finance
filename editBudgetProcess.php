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
	/* Process the data here */
	$catid = $_POST['catid'];
	$amount = $_POST['amount'];
	$replace = $_POST['replace'];
	$month = NULL;
	if(isset($_POST['month'])) {
		$month = $_POST['month'];
	}
	
	if($_POST['replace']) {
		/* Delete any local data */
		$query = "DELETE FROM budget WHERE (catid = $catid) AND (b_month IS NOT NULL)";
		$result = deleteData($query);
	}
	
	if($month !== NULL) {
		/* Add the local data */
		$query = "INSERT INTO budget (catid, b_month, b_amount) VALUES ($catid, $month, $amount)";
		$result = insertData($query);
		
	} else {
		/* Delete the old master data */
		$query = "DELETE FROM budget WHERE (catid = $catid) AND (b_month IS NULL)";
		$result = deleteData($query);
		
		/* Add the master data */
		$query = "INSERT INTO budget (catid, b_month, b_amount) VALUES ($catid, NULL, $amount)";
		
		$result = insertData($query);
	}
}

header("Location:./editBudget.php");
?>