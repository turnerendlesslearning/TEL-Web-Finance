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
include_once(dirname(__FILE__) . "/classes/Reconciler.php");

include_once(dirname(__FILE__) . "/includes/config.php");
include_once(dirname(__FILE__) . "/includes/security.php");
include_once(dirname(__FILE__) . "/includes/db.php");


//Security Check
if(!isLoggedIn()) {
	$_SESSION = array();
	session_destroy();
}

//Build any headers
$additionalHeaders = "<script language=\"JavaScript\" src=\"./tigra_calendar/calendar_us.js\"></script>";
$additionalHeaders .= "<link rel=\"stylesheet\" href=\"./tigra_calendar/calendar.css\">";
ob_start();
?>

<script type="text/javascript">
var newTransFormHidden = true;
var newTransFormBasicText = "";

function trim(stringToTrim) {
	return stringToTrim.replace(/^\s+|\s+$/g,"");
}
function ltrim(stringToTrim) {
	return stringToTrim.replace(/^\s+/,"");
}
function rtrim(stringToTrim) {
	return stringToTrim.replace(/\s+$/,"");
}
<script
	type="text/javascript" src="js/ajax.js"></script>
<script
	type="text/javascript" src="js/ajax-dynamic-list.js"></script>
<?php
$additionalHeaders .= ob_get_contents();
ob_end_clean();


//Get the account id
$aid = $_POST['accountid'];

//Get the account name
$accname = getAccountName($aid);

//Build the body
$content = "<h1>$accname</h1>" . PHP_EOL;
if(!isset($_SESSION['username'])) {
	ob_start();
	include("./login.php");
	$content .= ob_get_contents();
	ob_end_clean();
} else {
	$content .= "<a href=\"logout.php\">Logout</a> > <a href=\"./index.php\">Accounts</a> > <a href=\"./ledger.php?id=".$aid."\">Ledger</a> > Import";

	
	
	
	//Start Building Page Content
	ob_start();
	
	echo "<div id=\"importDiv\">";
	$filename = $_FILES['files']['tmp_name'];
	
	$handle = fopen($filename, "r");
	$counter = 1;
	echo "<form method=\"post\" action=\"import_process.php\" name=\"importForm\">";
	echo "<table>";
	while($csv = fgetcsv($handle)) {
		$tDate = $csv[0];
		$tDesc = $csv[1];
		$tAmount = $csv[2];
		$tBal = $csv[3];
		$tCheck = $csv[4];
		$tCat = $csv[5];
		
		
		$tm = strtotime($tDate);
		
		if(!$tm) {
			continue;
		}
		$tDate = date("Y-m-d", $tm);
		
		if($tCat == "") {
			$tCat = "unknown";
		}
		
		echo "<tr>";
		echo "<td>$counter</td>";
		echo "<td><input type=\"text\" size\"6\" name=\"{$counter}_tDate\" value=\"$tDate\" /></td>";
		echo "<td><input type=\"text\" size\"30\" name=\"{$counter}_tDesc\" value=\"$tDesc\" /></td>";
		echo "<td><input type=\"text\" size\"6\" name=\"{$counter}_tAmount\" value=\"$tAmount\" /></td>";
		echo "<td><input type=\"text\" size\"6\" name=\"{$counter}_tCheck\" value=\"$tCheck\" /></td>";
		echo "<td><input type=\"text\" size\"6\" name=\"{$counter}_tCat\" value=\"$tCat\" /></td>";		
		echo "</tr>";
		
		$counter++;		
	}

	echo "</table>";
	echo "<input type=\"hidden\" name=\"counter\" value=\"$counter\" />";
	echo "<input type=\"hidden\" name=\"accountid\" value=\"" . $_POST['accountid'] . "\" />";
	echo "<input type=\"submit\" value=\"submit\" />";
	echo "</form>";
	
	?>
<div id="messageBoxHolder" class="messageBoxHolder">
	<div id="messageBox" class="messageBox">
	&nbsp;
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
$pageTitle = "TEL Finance - Import";

//Import the template
include_once("./template.php");

?>