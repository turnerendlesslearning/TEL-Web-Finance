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

function dbConnect() {
	include_once("./includes/config.php");
	if(!defined('DB_HOST')) {
		return FALSE;
	}
	$mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
	if($mysqli->connect_errno) {
		return FALSE;
	}
	
	return $mysqli;
}
function selectData($queryString) {
	$mysqli = dbConnect();
	$result = $mysqli->query($queryString);
	
	if($mysqli->errno) {
		//An error, return the error message string
		return $mysqli->error;
	} else {
		//Return the result object
		return $result;
	}
}
function deleteData($queryString) {
	$mysqli = dbConnect();
	$mysqli->query($queryString);
	if($mysqli->errno) {
		echo $mysqli->error;
		echo "<p>$queryString</p>";
		die();
	}
}
function insertData($queryString) {
	$conn = dbConnect();
	$result = $conn->query($queryString);
	if($conn->errno) {
		die($conn->error);
	} else {
		return $conn->insert_id;
	}
}
function backupDatabase() {
	require_once("./includes/config.php");
	
	$backupFile = realpath("./") . "/data_backup/" . date("Y-m-d") . ".sql";
	if(file_exists($backupFile)) {
		return;
	}
	
	$command = "mysqldump --opt -h ".DB_HOST." -u ".DB_USER." --password=".DB_PASS." ".DB_NAME." > $backupFile";
	$retval = "";
	$result = system($command, $retval);

}
function getRecordCount($result) {
	return $result->num_rows;
}
function updateData($queryString) {
	return insertData($queryString);
}
function checkPassword($username, $password) {
	$data = selectData("SELECT * FROM users WHERE (username = '$username')");
	
	if(is_string($data) || !$data) {
		return FALSE;
	}
	
	$row = $data->fetch_assoc();
	
	$uniqueid = $row['uniqueid'];
	$pwhash = sha1(sha1($password).$uniqueid);

	if($pwhash == $row['password']) {
		return array($row['uniqueid'], $row['userid']);
	} else {
		die("HERE");
	}
	return FALSE;
}
function getAccounts() {
	$data = selectData("SELECT * FROM acc");
	if(is_string($data) || !$data) {
		return FALSE;
	}
	return $data;
}
function getCategories($incomeOrExpenses=null) {
    $query = "SELECT * FROM cat";
    if($incomeOrExpenses == "INCOME") {
        $query .= " WHERE (isincome=1)";
    } else if($incomeOrExpenses == "EXPENSES") {
        $query .= " WHERE (isincome=0)";
    }
    $query .= " ORDER BY isincome ASC, catname ASC";
    $data = selectData($query);
    if(is_string($data) || !$data) {
        return FALSE;
    }
    return $data;
}
function mergeCategories($sourceCategoryId, $targetCategoryId) {
    //Update all transactions having the sourceCatId to the targCatId
    $query = "UPDATE transparts SET catid=$targetCategoryId WHERE " . 
            "catid=$sourceCategoryId";
    updateData($query);
    deleteData("DELETE FROM budget WHERE catid=$sourceCategoryId");
    
    //Delete the source category
    $query = "DELETE FROM cat WHERE (catid=$sourceCategoryId)";
    deleteData($query);
}
function loadBudgetCategories($startDate, $endDate, $isincome, $parent=null) {
    $catquery = "SELECT * FROM cat WHERE " .
            "(cat.isincome = $isincome) AND ";
    if($parent == null) {
        $catquery .= "(cat.catname NOT LIKE '%:%') ";
    } else {
        $catquery .= "(cat.catname LIKE '".$parent->name.":%') ";
    }
    $catquery .= "ORDER BY cat.catname ASC";
    $catresult = selectData($catquery);
    if (!is_object($catresult) || $catresult->num_rows == 0) {
        return null;
    }

    $cats = array();
    while ($row = $catresult->fetch_assoc()) {
        //Create Category Object
        $c = new Category($row['catid']);
        
        //Add Basic Details
        $c->name = $row['catname'];
        $c->isincome = $row['isincome'];
        $c->prorated = $row['prorate'];
        $c->parent = $parent;
        /* TODO: This system is only capable of looking at one month.  Hence
         * we're getting the budgeted amount for this month based on the
         * startdate.  A more complex system will calculate for the start 
         * month, end month and intervening months... but that's for later.
         */
        $month = date("m", strtotime($startDate));
        $c->budgeted = getBudgetedAmount($c->id, $month);

        //Load any transactions in this category
        $c->loadTransactions($startDate, $endDate);
        
        //Add totals to parent category
        $c->addMoneyToParent($c->total, $c->budgeted);
        
        //Load any budget subcategories
        $c->subcategories = loadBudgetCategories($startDate, $endDate,
                $isincome, $c);
        
        //Add the category to the array
        $cats[] = $c;
    }
    return $cats;
}
function getBudgetedAmount($catid, $month) {
    //First check for a specified amount for that particular month
    $result = selectData("SELECT * FROM budget WHERE (catid=".$catid.
            ") AND (b_month=".$month.")");
    if(is_object($result) && ($result->num_rows > 0)) {
        $row = $result->fetch_assoc();
        return $row['b_amount'];
    } else {
        //If not, check for a NULL month (general amount)
        $result = selectData("SELECT * FROM budget WHERE (catid=$catid) AND ".
                "(b_month IS NULL)");
        if(is_object($result) && ($result->num_rows>0)) { 
            $row = $result->fetch_assoc();
            return $row['b_amount'];
        }
    }
    
    //If not, return 0
    return 0;
}
function getAccountName($accid) {
	$data = selectData("SELECT accname FROM acc WHERE (accid = $accid)");
	$row = getNextDataRow($data);
	return $row['accname'];
}
function getNextDataRow($result) {
	if(is_object($result)) {
		return $result->fetch_assoc();
	} else {
		return NULL;
	}
}
function createAccount($accountName, $sbalance, $sdate) {
	$result = insertData("INSERT INTO acc (accname, sbal, sdate) VALUES ('".addslashes($accountName) . "', $sbalance, '$sdate')");
	return $result;
}
function getTransactionTypes() {
	$res = selectData("SELECT * FROM transtypes");
	if(is_string($res) || !$res) {
		return FALSE;
	} else {
		return $res;
	}
}
function safe($string) {
	$conn = dbConnect();
	$safeString = $conn->real_escape_string($string);
	return $safeString;
}
function getCategoryId($catname) {
	$query = "SELECT * FROM cat WHERE (catname='$catname')";
	$result = selectData($query);
	
	if($result->num_rows > 0) {
		$row = getNextDataRow($result);
		return $row['catid'];
	} else {
		//Create the category
		$query = "INSERT INTO cat (catname) VALUES ('".strtolower($catname) . "')";
		$result = insertData($query);
		return $result;
	}
}
function getPayeeId($payeename) {
	$query = "SELECT * FROM payee WHERE (payeename='$payeename')";
	$result = selectData($query);
	
	if($result->num_rows > 0) {
		$row = getNextDataRow($result);
		return $row['payeeid'];
	} else {
		//Create the payee
		$query = "INSERT INTO payee (payeename) VALUES ('".$payeename . "')";
		$result = insertData($query);
		return $result;
	}
}
function getCategory($catid) {
$query = "SELECT * FROM cat WHERE (catid=$catid)";
	$result = selectData($query);
	
	if($result->num_rows > 0) {
		$row = getNextDataRow($result);
		return $row['catname'];
	} else {
		return FALSE;
	}
}
function getPayee($payeeid) {
$query = "SELECT * FROM payee WHERE (payeeid=$payeeid)";
	$result = selectData($query);
	
	if($result->num_rows > 0) {
		$row = getNextDataRow($result);
		return $row['payeename'];
	} else {
		return FALSE;
	}
}
function getTransactionType($typeid) {
	$query = "SELECT * FROM transtypes WHERE (transtypeid=$typeid)";
	$result = selectData($query);
	
	if($result->num_rows > 0) {
		$row = getNextDataRow($result);
		return $row['transtype'];
	} else {
		return FALSE;
	}
}
function getTransactionDate($transid) {
	$query = "SELECT * FROM trans WHERE (transid=$transid)";
	$result = selectData($query);
	
	if($result->num_rows > 0) {
		$row = getNextDataRow($result);
		return $row['transdate'];
	} else {
		return FALSE;
	}
}
function loadTransaction ($transid) {
	$res = selectData("SELECT * FROM trans WHERE (transid = ".$transid.")");
	if(!$res) {
		echo "Didn't Find Transaction";
		return FALSE;
	}
	
	$t = new Transaction();
	$t->transid = $transid;
	$row = getNextDataRow($res);
	if(!$row) {
		echo "Couldn't get the data row...";
		return FALSE;
	}
	
	//Get all basic data from the transaction row
	$t->number = $row['transnumber'];
	$t->typeid = $row['transtypeid'];
	$t->tdate = $row['transdate'];
	$t->payeeid = $row['transpayee'];
	$t->accountid = $row['accid'];
	$t->reconid = $row['reconid'];
	$t->balance = $row['balance'];
	
	//Get strings from ids for payee and transaction type
	$t->type = getTransactionType($t->typeid);
	$t->payee = getPayee($t->payeeid);

	if($t->payee == 'transfer') {
		//Load the account using the transaction number (which is the receiving acc#)
		$t->payee .= " [" . getAccountName($t->number) . "]";
	}
	
	//Get Transaction Parts
	$t->parts = getTransactionParts($t->transid);
	$t->calcTotal();
	
	return $t;
}
function loadTransactions($accountid, $startDate, $endDate) {
	$res = NULL;
	
	$tArray = array();
	$query = "SELECT transid FROM trans WHERE (accid = $accountid)";
	if($startDate != NULL) {
		$query .= " AND (transdate >= '$startDate')";
	}
	if($endDate != NULL) {
		$query .= " AND (transdate <= '$endDate')";
	}
	$query .= " ORDER BY transdate DESC, transid DESC";
	
	$res = selectData($query);
		
	while($row = getNextDataRow($res)) {
		$tArray[] = loadTransaction($row['transid']);
	}
	return $tArray;	
}
function getTransactionParts($transid) {
	$query = "SELECT * FROM transparts WHERE (transid = $transid)";
	$res = selectData($query);
	if(!$res || $res->num_rows == 0) {
		echo "ERROR: " . $query;
	}
	
	$tpart = array();
	
	while($row = getNextDataRow($res)) {
		$tp = new TransactionPart();
		$tp->transpartid = $transid;
		$tp->amount = $row['amount'];
		$tp->memo = $row['memo'];
		$tp->categoryid = $row['catid'];
		$tp->category = getCategory($row['catid']);
		
		$tpart[] = $tp;
	}
	
	return $tpart;
}
function getLedger($accountid, $startDate, $endDate) {
	//Create the Ledger Object
	$l = new Ledger();
	
	//get the account start date (only get transactions after this)
	$l->startDate = getAccountStartDate($accountid);
	//echo "ACCOUNT START DATE: " . $l->startDate . "<br />";
	
	//if startDate or endDate are time stamps, convert them to strings
	if(is_numeric($startDate)) {
		$startDate = date("Y-m-d", $startDate);
	}
	if(is_numeric($endDate)) {
		$endDate = date("Y-m-d", $endDate);
	}
	
	if(strtotime($startDate) < strtotime($l->startDate)) {
		$startDate = $l->startDate;
	}
	
	//Get the starting balance
	$l->startBalance = getStartingBalance($accountid);
	//echo "STARTING BAL: " . $l->startBalance . "<br />";
	
	//Get the Sub-starting balance (the original starting balance minus
	//any transactions that took place before our range of dates
	$preSpent = sumTransactions($accountid, $l->startDate, strtotime('-1 day', strtotime($startDate)));
	//echo "SPENT PRIOR TO THIS LEDGER: " . $preSpent . "<br />";
	$l->subStartBalance = $l->startBalance + $preSpent;
	//echo "SUB-STARTING BAL: " . $l->subStartBalance . "<br />";
	
	//Temp: Get the balance for the current ledger
	$l->total = sumTransactions($accountid, $startDate, $endDate);
	//echo "SPENT THIS RANGE: " . $l->total . "<br />";
	
	//Current Balance (at the bottom.. only useful in real life if the end date is today)
	$l->endBalance = $l->subStartBalance + $l->total;
	//echo "END BALANCE: " . $l->endBalance . "<br />";
	
	//Load all the transactions
	$l->transactions = loadTransactions($accountid, $startDate, $endDate);
	
	$l->calcIncomeAndExpenses();
	
	//Return the Ledger
	return $l;
}
/** 
 * 
 * Gets the sum of the transaction parts of these transactions.
 * Note that the start and end dates are inclusive.  Startdate and end date
 * could be Unix timestamps or strings that can be parsed by strtotime()
 * such as date("Y-m-d"). They can also be NULL to represent no start date,
 * or no end date.
 * @param int $accountid
 * @param mixed $startDate
 * @param mixed $endDate
 */
function sumTransactions($accountid, $startDate, $endDate) {
	if(is_numeric($startDate)) {
		$startDate = date("Y-m-d", $startDate);
	}
	if(is_numeric($endDate)) {
		$endDate = date("Y-m-d", $endDate);
	}
	
	$query = "SELECT SUM(amount) FROM transparts, trans WHERE (transparts.transid = trans.transid)";
	if($startDate != NULL) {
		$query .= " AND (trans.transdate >= '$startDate')";
	}
	if($endDate != NULL) {
		$query .= " AND (trans.transdate <= '$endDate')";
	}
	$query .= " AND (accid = $accountid)";
	
	$res = selectData($query);
	if(!$res) {
		die("Query Error");
	}
		
	$row = getNextDataRow($res);
	if(!$row) {
		echo $query;
		die("Get Data from Result Error");
	}
			
	return $row['SUM(amount)'];
}
function getStartingBalance($accountid) {
	$query = "SELECT sbal FROM acc WHERE (accid = $accountid)";
	$res = selectData($query);
	$row = getNextDataRow($res);
	return $row['sbal'];
}
function getAccountStartDate($accountid) {
	$query = "SELECT sdate FROM acc WHERE (accid = $accountid)";
	$res = selectData($query);
	$row = getNextDataRow($res);
	return $row['sdate'];	//returned as a string
}
function isTransactionReconciled($transid) {
    $query = "SELECT reconid FROM trans WHERE (transid = $transid)";
    $result = selectData($query);
    $row = getNextDataRow($result);
    if($row == NULL) return;
    if($row['reconid'] == NULL) {
        return false;
    } else {
        return $row['reconid'];
    }
}
function getUnfinishedReconId($accountid) {
	$query = "SELECT * FROM recon WHERE (accid=$accountid) AND (completed IS NULL)";
	$result = selectData($query);
	
	//Check for Error
	if(is_string($result)) {
		return FALSE;
	}
	
	//Check for Empty Set
	$num = getRecordCount($result);
	if(!$num) {
		return FALSE;
	}
	
	if($num > 1) {
		//The is a ghost unfinished reconciliation.
		//Future code should take care of this
	}
	
	$row = getNextDataRow($result);
	
	return $row['reconid'];
}
function getReconcileStartBalance($accountid) {
	$query = "SELECT * FROM recon WHERE (accid=$accountid) AND (completed IS NOT NULL) ORDER BY recdate DESC";
	$result = selectData($query);
	
	//Check for Error
	if(is_string($result)) {
		return 0;
	}
	
	//Check for Empty Set
	if(!getRecordCount($result)) {
		//Get the account starting balance
		return getStartingBalance($accountid);
	} else {
		$row = getNextDataRow($result);
		return $row['ebalance'];
	}
}
function getReconcileEndBalance($reconid) {
	$query = "SELECT * FROM recon WHERE (reconid=$reconid)";
	$result = selectData($query);
	
	//Check for Error
	if(is_string($result)) {
		die("Error");
	}
	
	$num = getRecordCount($result);
	
	if($num == 0) {
		die("No Record Found");
	}
	
	$row = getNextDataRow($result);
	return $row['ebalance'];
}
function getReconcileCloseDate($reconid) {
	$query = "SELECT * FROM recon WHERE (reconid = $reconid)";
	$result = selectData($query);
	
	$row = getNextDataRow($result);
	if($row) {
		return $row['recdate'];
	} else {
		return NULL;
	}
}
function createReconcile($accountId, $closeDate, $endBalance) {
	$query = "INSERT INTO recon (accid, recdate, ebalance, completed) VALUES ($accountId, '".
		date("Y-m-d", strtotime($closeDate))."', $endBalance, NULL)";
	$reconId = insertData($query);
	return $reconId;
}
function loadReconcileTransactions($accountId, $reconId) {
	$query = "SELECT * FROM trans WHERE (accid=$accountId) AND ((reconid IS NULL) OR (reconid = $reconId)) ORDER BY transdate ASC";
	$result = selectData($query);
	
	$retVal = array();
	while($row = getNextDataRow($result)) {
		$retVal[] = loadTransaction($row['transid']);
	}
	
	return $retVal;
}
function completeReconcile($reconId, $endBalance, $closeDate) {
	$query = "UPDATE recon SET completed=NOW(), ebalance=$endBalance, recdate='".date("Y-m-d", strtotime($closeDate))."' WHERE reconid=$reconId";
	updateData($query);
}
function getLastReconcileDate($accid) {
	$query = "SELECT * FROM recon WHERE (accid=$accid) ORDER BY recdate DESC LIMIT 1";
	$result = selectData($query);
	$num = getRecordCount($result);
	
	if($num== 0) {
		return FALSE;
	}
	
	$row = getNextDataRow($result);
	return $row['recdate'];
}
function getBalancePriorToNewTrans($accid, $newTransDate) {
	$query = "SELECT * FROM trans WHERE (accid=$accid) AND 
		(transdate < '$newTransDate') 
		ORDER BY transdate DESC, transid DESC LIMIT 1";
	$result = selectData($query);
	if($row = getNextDataRow($result)) {
            return $row['balance'];
	} else {
            /* There were not transactions selected (hence, none prior to
             * to this, so I can get the account starting balance.
             */
            return getStartingBalance($accid);
	}
}
function getAccountBalance($accid) {
	$query = "SELECT * FROM trans WHERE (accid=$accid) 
		ORDER BY transdate DESC, transid DESC LIMIT 1";
	$result = selectData($query);
	if($row = getNextDataRow($result)) {
		return $row['balance'];
	} else {
            return NULL;
	}
}
function updateBalanceForAllSubsequent($accid, $transdate) {
	$balance = getBalancePriorToNewTrans($accid, $transdate);
	
	//SELECT ALL
	$query = "SELECT trans.balance, trans.transid, trans.transdate, 
		SUM(transparts.amount) AS amount
		FROM trans, transparts WHERE (trans.transid = transparts.transid) 
		AND (trans.accid = $accid)  
		AND (trans.transdate >= '$transdate') GROUP BY 
		trans.transid ORDER BY transdate ASC, transid ASC";
	
	
	$result = selectData($query);
	
	//Update
	while($row = getNextDataRow($result)) {
		$transid = $row['transid'];
		$amount = $row['amount'];
		$balance += $amount;
		$query = "UPDATE trans SET balance=$balance WHERE transid=$transid";
		updateData($query);
	}
}
function sumTransactionsByCategory($accountid, $startDate, $endDate, $catid) {
	if(is_numeric($startDate)) {
		$startDate = date("Y-m-d", $startDate);
	}
	if(is_numeric($endDate)) {
		$endDate = date("Y-m-d", $endDate);
	}
	
	$query = "SELECT SUM(amount) FROM transparts, trans WHERE 
		(transparts.transid = trans.transid) AND 
		(transparts.catid = $catid)";
	if($startDate != NULL) {
		$query .= " AND (trans.transdate >= '$startDate')";
	}
	if($endDate != NULL) {
		$query .= " AND (trans.transdate <= '$endDate')";
	}
	$query .= " AND (accid = $accountid)";
	
	$res = selectData($query);
	if(!$res) {
		die("Query Error");
	}
		
	$row = getNextDataRow($res);
	if(!$row) {
		echo $query;
		die("Get Data from Result Error");
	}
			
	return $row['SUM(amount)'];
}
function getCategoryBudget($catid) {
	$query = "SELECT b_amount FROM budget WHERE (catid=$catid)";
	$result = selectData($query);
	if(is_object($result)) {
		$row = $result->fetch_assoc();
		return $row['b_amount'];
	} else {
		return null;
	}
	
}
function getTotalSpent($accid, $startDate, $endDate) {
	$query = "SELECT SUM(transparts.amount) FROM transparts, trans WHERE 
		(transparts.transid = trans.transid) AND 
		(transparts.amount < 0)";
	if($startDate != NULL) {
		$query .= " AND (trans.transdate >= '$startDate')";
	}
	if($endDate != NULL) {
		$query .= " AND (trans.transdate <= '$endDate')";
	}
	$query .= " AND (trans.accid = $accid)";
	
	$result = selectData($query);

	if(is_object($result)) {
		$row = $result->fetch_row();
		return $row[0];
	} else {
		return 0;
	}
}
function getTotalIncome($accid, $startDate, $endDate) {
	$query = "SELECT SUM(transparts.amount) FROM transparts, trans WHERE 
		(transparts.transid = trans.transid) AND 
		(transparts.amount > 0)";
	if($startDate != NULL) {
		$query .= " AND (trans.transdate >= '$startDate')";
	}
	if($endDate != NULL) {
		$query .= " AND (trans.transdate <= '$endDate')";
	}
	$query .= " AND (trans.accid = $accid)";
	
	$result = selectData($query);

	if(is_object($result)) {
		$row = $result->fetch_row();
		return $row[0];
	} else {
		return 0;
	}	
}
function fix() {
$query = "SELECT * FROM trans WHERE (accid=1) AND (transdate='2011-06-15')";
$result = selectData($query);
while($row = getNextDataRow($result)) {
	$query = "UPDATE trans SET balance = ".($row['balance'] + 100).
		" WHERE (transid=".$row['transid'].")";
	updateData($query);
}
}

backupDatabase();
?>