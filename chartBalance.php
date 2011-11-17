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


//Security Check
if(!isLoggedIn()) {
	$_SESSION = array();
	session_destroy();
}

unset($_SESSION['s']);
unset($_SESSION['e']);

$qs = "id=" . $_GET['id'];
if(isset($_GET['s'])) {
	$qs .= "&s=" . $_GET['s'];
	$_SESSION['s'] = $_GET['s'];
}
if(isset($_GET['e'])) {
	$qs .= "&e=" . $_GET['e'];
	$_SESSION['e'] = $_GET['e'];
}

//Build any headers
$additionalHeaders = "<script language=\"JavaScript\" src=\"./tigra_calendar/calendar_us.js\"></script>";
$additionalHeaders .= "<link rel=\"stylesheet\" href=\"./tigra_calendar/calendar.css\">";
$additionalHeaders .= "<script type=\"text/javascript\" src=\"includes/js/swfobject.js\"></script><script type=\"text/javascript\">";
$additionalHeaders .= "swfobject.embedSWF(
\"includes/open-flash-chart.swf\", \"my_chart\",
\"800\", \"600\", \"9.0.0\", \"expressInstall.swf\",
{\"data-file\":\"oftdata.php?".$qs."\"} );

</script>";
//ob_start();
//$additionalHeaders .= ob_get_contents();
//ob_end_clean();


//Build the body
$content = "<h1>Chart</h1>" . PHP_EOL;
if(!isset($_SESSION['username'])) {
	ob_start();
	include("./login.php");
	$content .= ob_get_contents();
	ob_end_clean();
} else {
	//$content .= "<a href=\"logout.php\">Logout</a> > <a href=\"./index.php\">Accounts</a> > Ledger";

	//Start Building Page Content
	ob_start();
	?>

 <div id="my_chart"></div>
 
 <?php
$content .= ob_get_contents();
ob_end_clean();
}

//Set the page title
$pageTitle = "TEL Finance - Chart Balance";

//Import the template
include_once("./template.php");

?>
 