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
include_once(dirname(__FILE__) . "/classes/Ledger.php");
include_once(dirname(__FILE__) . "/includes/config.php");
include_once(dirname(__FILE__) . "/includes/security.php");
include_once(dirname(__FILE__) . "/includes/db.php");

//Security Check
if(!isLoggedIn()) {
	$_SESSION = array();
	session_destroy();
}

//Build any headers
$additionalHeaders = "";

//Build the body
$content = "<h1>TEL Finance 2011</h1>" . PHP_EOL;
if(!isset($_SESSION['username'])) {
	ob_start();
	include("./login.php");
	$content .= ob_get_contents();
	ob_end_clean();
} else {
	$content .= "<a href=\"logout.php\">Logout</a> > Accounts > <a href=\"addAccount.php\">Add an Account</a>";
	
	//Start Building Page Content
	ob_start();
	?>
	<div id="accountsDiv">
	<table class="tableStyle1">
	<tr class="header"><td>Accounts</td><td>Last Rec.</td><td>Balance</td></tr>
	<?php 
	$acc = getAccounts();
	$counter = 0;
	$netWorth = 0;
	while($row = getNextDataRow($acc)) {
		$l = getLedger($row['accid'], getAccountStartDate($row['accid']), date("Y-m-d"));
		$l->calcIncomeAndExpenses();
		
		echo "<tr";
		if($counter % 2) {
			echo " class=\"odd\"";
		}
		echo "><td><a href=\"./ledger.php?id=" . $row['accid'] . "\">" . $row['accname'] . "</a></td>";
		echo "<td style=\"font-size:10px;\">";
		$lastRec = getLastReconcileDate($row['accid']);
		if($lastRec == NULL) {
			echo "-never-";
		} else {
			echo date("m-d-Y", strtotime($lastRec));
		}
		echo "</td>";
		echo "<td>$" . money_format("%n", $l->endBalance) . "</td></tr>" . PHP_EOL;
		$netWorth += $l->endBalance;
		$counter++;
	}
	if($counter == 1) {
		echo "<tr class=\"footer\"><td>1 account</td><td></td><td></td></tr>" . PHP_EOL;
	} else {
		echo "<tr class=\"footer\"><td>" . $counter . " accounts</td><td></td><td><b>$".money_format("%n", $netWorth)."</b></td></tr>" . PHP_EOL;
	}
	?>
	</table>
	</div>
	
	<br />
	<div id="otherDiv">
	<b>Other</b><br />
	<a href="editBudget.php">Edit Budget</a><br />
        <a href="editCategories.php">Edit Categories</a><br />
	</div>
	<br />
	<div id="reportsDiv">
	<b>Reports</b><br />
	<a href="viewReport.php?t=IBC">Income By Category</a><br />
	<a href="viewReport.php?t=EBC">Expense By Category</a><br />
	<a href="viewReport.php?t=IEBC">Income and Expense By Category</a><br />
	<a href="budgetReport.php">Budget Report by Month</a>
	<a href="budgetReport.php?scope=year">Budget Report by Year</a>
	</div>
	
	
	<?php
	$content .= ob_get_contents();
	ob_end_clean();
}

//Set the page title
$pageTitle = "TEL Finance";

//Import the template
include_once("./template.php");

?>
