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

<?php

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

	
	//delete the transaction and it's parts, then proceed to re-add it	
	if($_POST['transpayee'] != 'transfer') {
		$accid = $_POST['accid'];
		
		//Get the TransDate
		$transdate = getTransactionDate($_POST['transid']);
		
		deleteData("DELETE FROM trans WHERE (transid = " . $_POST['transid'] . ")");
		deleteData("DELETE FROM transparts WHERE (transid = ". $_POST['transid'] . ")");
		
		//Update balances
		updateBalanceForAllSubsequent($accid, $transdate);
	} else {
		//delete the corresponding transaction from the transferaccount
		//Get the main transaction first
		$res = selectData("SELECT * FROM trans, transparts WHERE (trans.transid = transparts.transid) AND (trans.transid = " . $_POST['transid'] . ")");
		$row = getNextDataRow($res);
		
		if(!is_array($row)) {
			die("ERROR: <br />" . __FILE__ . "<br />" . __LINE__);
		}
		
		
		$mainDate = $row['transdate'];
		$cat = $row['catid'];
		$amo = -($row['amount']);
		$mem = $row['memo'];
		$accid = $_POST['accid'];
		$targAcct = $row['transnumber'];
		
		$query = "DELETE trans.*, transparts.* FROM trans, transparts WHERE (trans.transnumber = $accid) AND (trans.accid = $targAcct) AND (trans.transid = transparts.transid) AND (transparts.catid = $cat) AND (trans.transdate = '$mainDate') AND (transparts.amount = $amo)";
		deleteData($query);
		
		deleteData("DELETE FROM trans WHERE (transid = " . $_POST['transid'] . ")");
		deleteData("DELETE FROM transparts WHERE (transid = ". $_POST['transid'] . ")");
		
		
		//Update Balances
		updateBalanceForAllSubsequent($accid, $mainDate);
		updateBalanceForAllSubsequent($targAcct, $mainDate);
	}
}

header("Location:./ledger.php?id=" . $accid);
?>