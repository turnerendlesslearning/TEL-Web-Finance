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

//
error_reporting(E_ALL);

//Includes
include_once(dirname(__FILE__) . "/classes/Transaction.php");
include_once(dirname(__FILE__) . "/classes/TransactionPart.php");
include_once(dirname(__FILE__) . "/classes/Ledger.php");

include_once(dirname(__FILE__) . "/includes/config.php");
include_once(dirname(__FILE__) . "/includes/security.php");
include_once(dirname(__FILE__) . "/includes/db.php");

setlocale(LC_MONETARY, 'en_US');

//Security Check
if(!isLoggedIn()) {
	$_SESSION = array();
	session_destroy();
}

//Build any headers
ob_start();
$additionalHeaders .= ob_get_contents();
ob_end_clean();


//Get the account id
$reportType = constant($_GET['t']);


//Build the body
$content = "<h1>Report: $reportType</h1>" . PHP_EOL;
if(!isset($_SESSION['username'])) {
	ob_start();
	include("./login.php");
	$content .= ob_get_contents();
	ob_end_clean();
} else {
	$content .= "<a href=\"logout.php\">Logout</a> > <a href=\"./index.php\">Accounts</a> > Reports";
	
	//Start Building Page Content
	ob_start();
	
	//Default view is this month
	$y = date("Y");
	$m = date("m");
	$d = "01";
	$startDate = "$y-$m-$d";
	$endDate = date("Y-m-d");
	
	if(isset($_GET['e'])) {
		$endDate = $_GET['e'];
	}
	if(isset($_GET['s'])) {
		$startDate = $_GET['s'];
	}

	class cat {
		public $name="";
		public $id=0;
		public $total=0.0;
		public $parts;
		function __construct() {
			$parts = array();
		}
		
		function __toString() {
			$s = "<tr class=\"header\"><td>$this->name</td><td colspan=\"3\"></td><td>TOTAL: $" . money_format("%n", $this->total) . "</td></tr>" . PHP_EOL;
			
			foreach($this->parts as $p) {
				$s .= "<tr><td></td><td>" . $p[0] . "</td><td>" . $p[1] . "</td><td>" . $p[2] . "</td><td>$" . money_format('%n', $p[3]) . "</td></tr>" . PHP_EOL;
			}			
			return $s;
		}
	}
	
	//Get all the transaction parts
	$query = "SELECT * FROM trans, transparts WHERE (transparts.transid = trans.transid) ";
	if($_GET['t'] == "IBC") {
		$query .= "AND (transparts.amount > 0) ";
	} else if ($_GET['t'] == "EBC") {
		$query .= " AND (transparts.amount < 0) ";
	}
	$query .= " AND (transdate <= '$endDate') AND (transdate >= '$startDate') ";
	$query .= "ORDER BY transdate ASC";
	$res = selectData($query);
	
	$cats = array();
	
	function searchCats($catid, $cats) {
		$foundCatId = FALSE;
		for($i = 0; $i < count($cats); $i++) {
			if($cats[$i]->id == $catid) {
				return $i;
			}
		}
		return $foundCatId;
	}
	
	while($row = getNextDataRow($res)) {
		$id = $row['catid'];
		$idInCats = searchCats($id, $cats);
		
		if($idInCats !== FALSE) {
			//Add this data to the current pile
			$cats[$idInCats]->total += $row['amount'];
			$details = array($row['transdate'], getPayee($row['transpayee']), $row['memo'], $row['amount']);
			$cats[$idInCats]->parts[] = $details;
		} else {
			//Create a new cat object and add to the list with the prelim info
			$c = new cat();
			$c->id = $id;			
			$c->name = getCategory($id);
			$c->total += $row['amount'];
			$details = array($row['transdate'], getPayee($row['transpayee']), $row['memo'], $row['amount']);
			$c->parts[] = $details;
			$cats[] = $c;
		}
	}
	
	echo "<br /><br />";
	
	sort($cats);
	
	$masterTotal = 0;
	echo "<table class=\"tableStyle1\">" . PHP_EOL;
	foreach($cats as $c) {
		echo $c . PHP_EOL;
		$masterTotal += $c->total;
	}
	echo "</table>" . PHP_EOL;
	echo "<p><span style=\"font-weight:bold;color:blue;\">$masterTotal</span></p>" . PHP_EOL;
	?>
	
	
	
	
	<div id="tintedPane" class="tintedPane"></div>
	<?php
	$content .= ob_get_contents();
	ob_end_clean();
}

//Set the page title
$pageTitle = "TEL Finance - Reports";

//Import the template
include_once("./template.php");

?>