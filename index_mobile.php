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
include_once(dirname(__FILE__) . "/classes/Ledger.php");
include_once(dirname(__FILE__) . "/includes/config.php");
include_once(dirname(__FILE__) . "/includes/security.php");
include_once(dirname(__FILE__) . "/includes/db.php");

//Security Check
if(!isLoggedIn()) {
	$_SESSION = array();
	session_destroy();
	
	//Check for autologin
	if(isset($_GET['u']) && isset($_GET['p'])) {
		header("Location:./login_process.php?u=" . $_GET['u'] . "&p=" .
			$_GET['p'] . "&m=true");
		die();
	}
}

//Build any headers
$additionalHeaders = "";

setlocale(LC_MONETARY, EN_US);
function m($money) {
	return "$" . money_format("%n", $money);
}

//Build the body
$content = "<b>TEL Finance 2011</b><br />" . PHP_EOL;
if(!isset($_SESSION['username'])) {
	ob_start();
	include("./login.php");
	$content .= ob_get_contents();
	ob_end_clean();
} else {
	ob_start();
	$defaultAccount = 1;
	
	$budgetMonthStart = 21;
	$budgetMonthEnd = 20;
	
	$date = date("d", time());
	$startDate = null;
	$endDate = null;
	
	if($date <= "20") {
		$month = date("m", time()) - 1;
		$year = date("Y", time());
		if($month == 0) { 
			$month = 12;
			$year--;
		}
		$startDate = $year . "-" . $month . "-21";
		$endDate = $year . "-" . date("m", time()) . "-20";
	} else {
		$startDate = date("Y", time()) . "-" . date("m", time()) . "-21";
		$month = date("m", time()) + 1;
		if($month > 12) {
			$month = 1;
			$year = date("Y", time()) + 1;
		} else {
			$year = date("Y", time());
		}
		$endDate = $year . "-" . $month . "-20";
	}

	
	echo "<b>Balance</b>: " . m(getAccountBalance($defaultAccount)) . "<br />" . PHP_EOL;
	echo "<b>Out</b>: " . m(getTotalSpent($defaultAccount, $startDate, $endDate)) . 
		"<br />" . PHP_EOL;
	echo "<b>In</b>: " . m(getTotalIncome($defaultAccount, $startDate, $endDate)) . 
		"<br />" . PHP_EOL;
	echo "<hr>";
	echo "<b>House/Groc.</b>: " . 
		m(sumTransactionsByCategory($defaultAccount, $startDate, $endDate, 17) +
		sumTransactionsByCategory($defaultAccount, $startDate, $endDate, 1)).
		"/" . m(getCategoryBudget(1) + getCategoryBudget(17)).  
		"<br />" . PHP_EOL;
	echo "<b>Home Repair</b>: " . 
		m(sumTransactionsByCategory($defaultAccount, $startDate, $endDate, 2)) .
		"/" . m(getCategoryBudget(2)) .
		"<br />" . PHP_EOL;
	echo "<b>Eating Out</b>: " . 
		m(sumTransactionsByCategory($defaultAccount, $startDate, $endDate, 19)) .
		"/" . m(getCategoryBudget(19)) .
		"<br />" . PHP_EOL;
	echo "<b>Gas</b>: " . 
		m(sumTransactionsByCategory($defaultAccount, $startDate, $endDate, 36)) .
		"/" . m(getCategoryBudget(36)) .
		"<br />" . PHP_EOL;
	echo "<b>Misc/Unknown</b>: " . 
		m(sumTransactionsByCategory($defaultAccount, $startDate, $endDate, 65) +
		sumTransactionsByCategory($defaultAccount, $startDate, $endDate, 23)).
		"/". m(getCategoryBudget(65) + getCategoryBudget(23)) . 
		"<br />" . PHP_EOL;
	
	echo "<hr>";
	
	//Add a Transaction
	?>
	<form method="post" action="addTransactionProcess.php">
	<input type="hidden" name="accid" value="<?php echo $defaultAccount;?>" />
	Date:<br />
	<input type="text" name="transdate" value="<?php echo 
		date("Y-m-d"); ?>" /><br />
	Type:<br />
	<select name="ttype">
		<?php 
			$result = selectData("SELECT * FROM transtypes");
			
			while($row = $result->fetch_assoc()) {
				echo "<option value=\"" . $row['transtypeid'] . "\"";
				if($row['transtype'] == "debit") {
					echo " selected";
				}
				echo ">" .
					$row['transtype'] . "</option>" . PHP_EOL;
			}
		?>
	</select><br />
	Number:<br />
	<input type="text" name="transnumber" value="0" /><br />
	Payee:<br />
	<select name="transpayeeid">
		<?php 
			$result = selectData("SELECT * FROM payee ORDER BY payeename ASC");
			
			while($row = $result->fetch_assoc()) {
				echo "<option value=\"" . $row['payeeid'] . "\">" .
					$row['payeename'] . "</option>" . PHP_EOL;
			}
		?>
	</select><br />
	<input type="text" name="payeename" /><br />
	Category:<br />
	<select name="catid">
		<?php 
			$result = selectData("SELECT * FROM cat ORDER BY catname ASC");
			
			while($row = $result->fetch_assoc()) {
				echo "<option value=\"" . $row['catid'] . "\">" .
					$row['catname'] . "</option>" . PHP_EOL;
			}
		?>
	</select><br />
	<input type="text" name="catname" /><br />
	Amount:<br />
	<input type="text" name="amount" value="-" /><br />
	Memo:<br />
	<input type="text" name="memo" /><br />
	<input type="submit" value="Post" />
	
	</form>
	
	<?php
		
	$content .= ob_get_contents();
	ob_end_clean();
}

//Set the page title
$pageTitle = "TEL Finance";

//Import the template
include_once("./template.php");

?>
