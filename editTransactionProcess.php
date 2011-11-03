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

		$dQuery = "DELETE FROM trans WHERE (transid = " . $_POST['transid'] . 
			")";
		deleteData($dQuery);
		$dQuery = "DELETE FROM transparts WHERE (transid = ". 
			$_POST['transid'] . ")";
		deleteData($dQuery);
		
		
		
		//Assemble Data for a Regular Transaction
		$accid = $_POST['accid'];
		$transdate = date("Y-m-d", strtotime($_POST['transdate']));
		$transtype = $_POST['ttype'];
		$transnumber = $_POST['transnumber'];
		if(trim($transnumber) == "") {
			$transnumber = 0;
		}		
		$transpayee = safe($_POST['transpayee']);
		
		
		//Get the balance prior to this transaction
		//$oldBalance = getBalancePriorToNewTrans($accid, $transdate);
		
		//Find out of the payee exists, and if not, create it and get its id
		$payeeid = getPayeeId($transpayee);
		if(!payeeid) {
			die("Couldn't get the payee id or create it.");
		}
		
		$query = "INSERT INTO trans (transdate, transtypeid, transnumber, 
			transpayee, accid) VALUES ('$transdate', $transtype, 
			$transnumber, $payeeid, $accid)";
		$result = insertData($query);
		
		if(!$result) {
			die("ERROR");
		}
		$transid = $result;
		$totalAmount = 0;
		
		foreach($_POST as $key => $value) {
			if((strpos($key, "cat") === 0) && (strpos($key, "_ID") === FALSE) 
				&& (strpos($key, "hidden") === FALSE)) {
				//get the id
				$partId = str_replace("cat", "", $key);
				
				$cat = $_POST['cat' . $partId];
				$memo = $_POST['mem' . $partId];
				$amount = $_POST['amo' . $partId];
				
				//Add this to the total amount for this transaction
				$totalAmount += (float)$amount;
				
				//Find out of the category exists, and if not, create it and 
					//get its id
				$catid = getCategoryId($cat);
				if(!catid) {
					die("Couldn't get the category id or create it.");
				}
				
				$query = "INSERT INTO transparts 
					(transid, catid, memo, amount) VALUES 
					($transid, $catid, '$memo', $amount)";
				$result = insertData($query);
				if(!$result) {
					echo $key;
					die($query);
				}
			}
		}
		
		//Update the transaction to set the proper balance
		//$newBalance = $oldBalance + $totalAmount;
		//$query = "UPDATE trans SET balance=$newBalance WHERE transid=$transid";
		//$result=updateData($query);
		
		//Update the balance for all subsequent transactions
		updateBalanceForAllSubsequent($accid, $transdate); 
//			getBalancePriorToNewTrans($accid, $transdate));
	} else {
		//delete the corresponding transaction from the transferaccount
		//Get the main transaction first
		$res = selectData("SELECT * FROM trans, transparts WHERE 
			(trans.transid = transparts.transid) AND (trans.transid = " . 
			$_POST['transid'] . ")");
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
		
		//Get the balance prior to this transaction
		//$oldBalance = getBalancePriorToNewTrans($accid, $transdate);
		//$oldRBalance = getBalancePriorToNewTrans($accidr, $newTransDate);
		
		$query = "DELETE trans.*, transparts.* FROM trans, transparts WHERE 
			(trans.transnumber = $accid) AND (trans.accid = $targAcct) AND (trans.transid = transparts.transid) AND (transparts.catid = $cat) AND (trans.transdate = '$mainDate') AND (transparts.amount = $amo)";
		deleteData($query);
		
		deleteData("DELETE FROM trans WHERE (transid = " . $_POST['transid'] . 
			")");
		deleteData("DELETE FROM transparts WHERE (transid = ". 
			$_POST['transid'] . ")");
		
		//Assemble data for a transfer
		$accid = $_POST['accid'];
		$accidr = $_POST['transferAccount'];
		$transdate = date("Y-m-d", strtotime($_POST['transdate']));
		$transtype = $_POST['ttype'];
		$transnumber = $accidr;
		$payeeid = getPayeeId('transfer');
		
		//Create the outgoing transfer transaction
		$q = "INSERT INTO trans (transdate, transtypeid, transnumber, 
			transpayee, accid) VALUES ('$transdate', $transtype, 
			$transnumber, $payeeid, $accid)";
		$result = insertData($q);
		if(!$result) {
			die("ERROR");
		}
		$outtransid = $result;
		
		//Create the incoming transfer transaction
		$transnumber = $accid;
		$q = "INSERT INTO trans (transdate, transtypeid, transnumber, 
			transpayee, accid) VALUES ('$transdate', $transtype, $transnumber, 
			$payeeid, $accidr)";
		$result = insertData($q);
		if(!$result) {
			die("ERROR");
		}
		$intransid = $result;
		
		//Prepare Transpart Data
		$cat = $_POST['cat1'];
		$memo = $_POST['mem1'];
		$amount = $_POST['amo1'];
		
		//Find out of the category exists, and if not, create it and get its id
		$catid = getCategoryId($cat);
		if(!catid) {
			die("Couldn't get the category id or create it.");
		}
		
		//Create the outgoing transpart
		$q = "INSERT INTO transparts (transid, catid, memo, amount) VALUES 
			($outtransid, $catid, '$memo', $amount)";
		$result = insertData($q);
		if(!$result) {
			echo $key;
			die($query);
		}
		
		//Create the incoming transpart
		$q = "INSERT INTO transparts (transid, catid, memo, amount) VALUES 
			($intransid, $catid, '$memo', ".-($amount) .")";
		$result = insertData($q);
		if(!$result) {
			echo $key;
			die($query);
		}
		
		//Update the ledger balance of the transaction in both accounts
		//$newBalance = $oldBalance + $amount;
		//$newRBalance = $oldRBalance + $amount;
		//$query = "UPDATE trans SET balance=$newBalance WHERE 
		//	transid=$outtransid";
		//$result=updateData($query);
		//$query = "UPDATE trans SET balance=$newRBalance WHERE 
		//	transid=$intransid";
		//$result=updateData($query);
		
		//Update balance in all subsequent trans in both accounts
		updateBalanceForAllSubsequent($accid, $transdate);//, $newBalance);
		updateBalanceForAllSubsequent($accidr, $transdate);//, $newBalance);
		
	}
}

header("Location:./ledger.php?id=" . $accid);
?>