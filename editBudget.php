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

//
error_reporting(E_ALL);

//Includes
include_once(dirname(__FILE__) . "/classes/Transaction.php");
include_once(dirname(__FILE__) . "/classes/TransactionPart.php");
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
$additionalHeaders = "<link rel=\"stylesheet\" href=\"./tigra_calendar/calendar.css\">";
ob_start();
?>
<script type="text/javascript">
function cancelEditing() {
	document.getElementById('tintedPane').style.display = 'none';
	document.getElementById('messageBoxHolder').style.display = 'none';
	document.getElementById('editMasterDiv').style.display = 'none';
	document.getElementById('editLocalDiv').style.display = 'none';
}
function editMaster(catid, catname, amount) {
	document.getElementById('tintedPane').style.display = 'block';
	document.getElementById('messageBoxHolder').style.display = 'block';
	document.getElementById('catNameSpan').innerHTML = catname;
	document.getElementById('editMasterDiv').style.display = 'block';
	document.editMasterForm.catid.value = catid;
	document.editMasterForm.amount.value = amount;
	document.editMasterForm.amount.focus();
}
function editLocal(catid, catname, amount, month) {
	document.getElementById('tintedPane').style.display = 'block';
	document.getElementById('messageBoxHolder').style.display = 'block';
	document.getElementById('catNameSpan').innerHTML = catname;
	document.getElementById('editLocalDiv').style.display = 'block';
	document.editLocalForm.catid.value = catid;
	document.editLocalForm.amount.value = amount;
	document.editLocalForm.month.value = month;
	document.editLocalForm.amount.focus();
}

function validateEditMasterForm() {
	var result = confirm("Would you like to erase any specific month values?\n\nChoose 'confirm' to overwrite old values.\nChoose 'cancel' to only use this as a master value.");
	if(result) {
		document.editMasterForm.replace.value = 1;
	} else {
		document.editMasterForm.replace.value = 0;
	}
	document.editMasterForm.submit();
}
function validateEditLocalForm() {
	document.editLocalForm.submit();
}
</script>

<?php
$additionalHeaders .= ob_get_contents();
ob_end_clean();


//Build the body
$content = "<h1>Edit Budget</h1>" . PHP_EOL;
if(!isset($_SESSION['username'])) {
	ob_start();
	include("./login.php");
	$content .= ob_get_contents();
	ob_end_clean();
} else {
	$content .= "<a href=\"logout.php\">Logout</a> > <a href=\"./index.php\">Accounts</a> > Edit Budget";

	//Start Building Page Content
	ob_start();
	?>

<div id="budgetDiv">
<table id="budgetTable" class="tableStyle1">
<tr class="header"><td>Category</td><td>January</td><td>February</td><td>March</td><td>April</td><td>May</td><td>June</td><td>July</td><td>August</td><td>September</td><td>October</td><td>November</td><td>December</td><td>Year</tr>

<?php 
//Get all the categories
$res = selectData("SELECT * FROM cat ORDER BY catname ASC");
$counter = 0;
$monthTotals = array(0,0,0,0,0,0,0,0,0,0,0,0);

//WEND through the categories
while($row = getNextDataRow($res)) {	
	$catTotal = 0;
	
	//See if there is a master amount budgeted
	$master = 0;
	$res2 = selectData("SELECT * FROM budget WHERE (catid=". $row['catid'] .") AND (b_month IS NULL)");
	if($row2 = getNextDataRow($res2)) {
		$master = $row2['b_amount'];
	}
	
	if($counter % 2) {
		echo "<tr class=\"odd\">";
	} else {
		echo "<tr>";
	}
	
	echo "<td ondblclick=\"editMaster(".$row['catid'].", '".$row['catname']."', $master);\">" . $row['catname'] . "</td>";
	for($i = 0; $i < 12; $i++) {
		//Check to see if there is a specific amount budgeted for the month
		$local = 0;
		$res_local = selectData("SELECT * FROM budget WHERE (catid = ".$row['catid'].") AND (b_month=$i)");
		if($row_local = getNextDataRow($res_local)) {
			$local  = $row_local['b_amount'];
		}
		echo "<td ondblclick=\"editLocal(".$row['catid'].", '".$row['catname']."', $local, $i);\">";
		if($local) {
			echo "$" . money_format("%n",$local);
			$monthTotals[$i] += $local;
			$catTotal += $local;
		} else if($master) {
			echo "$" . money_format("%n", $master);
			$monthTotals[$i] += $master;
			$catTotal += $master;
		} else {
			echo "-";
		}
		echo "</td>";
	}
	
	echo "<td>$".money_format("%n", $catTotal) . "</td>";
	echo "</tr>" . PHP_EOL;
	$counter++;
}

//Output the Footer Row
echo "<tr class=\"footer\">";
echo "<td></td>";

$yearTotal = 0;
foreach($monthTotals as $t) {
	$yearTotal += $t;
	echo "<td>$".money_format("%n", $t) . "</td>";
}
echo "<td>$".money_format("%n", $yearTotal) . "</td>";
echo "</tr>" . PHP_EOL;
?>



</table>
</div>

<div id="messageBoxHolder" class="messageBoxHolder">
<div id="messageBox" class="messageBox">
<h1>Edit Master Category</h1>
<b>Category</b>:<span id="catNameSpan"></span><br />

<div id="editMasterDiv">
<form name="editMasterForm" method="post" action="editBudgetProcess.php">
<input type="hidden" name="catid" /><input type="hidden" name="replace" />
<input type="text" size="10" name="amount" /><br />
<input type="button" value="cancel" onclick="cancelEditing();" /> <input type="button" value="submit" onclick="validateEditMasterForm();"/>
</form>
</div>

<div id="editLocalDiv">
<form name="editLocalForm" method="post" action="editBudgetProcess.php">
<input type="hidden" name="catid" /><input type="hidden" name="month" />
<input type="text" size="10" name="amount" /><br />
<input type="button" value="cancel" onclick="cancelEditing();" /> <input type="button" value="submit" onclick="validateEditLocalForm();"/>
</form>
</div>

</div>
</div>
<div id="tintedPane"
	class="tintedPane"></div>
<?php
$content .= ob_get_contents();
ob_end_clean();
}

//Set the page title
$pageTitle = "TEL Finance - Ledger";

//Import the template
include_once("./template.php");

?>