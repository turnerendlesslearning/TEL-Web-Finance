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
	$content .= "<a href=\"logout.php\">Logout</a> > <a href=\"./index.php\">Accounts</a> > Add an Account";
	
	//Start Building Page Content
	ob_start();
	?>
	<div id="addAccountsDiv" class="contentDiv">
	<form name="addAccountForm" method="post" action="addAccountProcess.php">
	Account Name: <input type="text" name="accountName" size="55" /><br />
	Starting Balance: <input type="text" name="startingBalance" size="35" /><br />
	Opening Date: <input type="text" name="openDate" size="15" />
	<input type="submit" value="Create Account" />
	</form>
	</div>
	
	
	<?php
	$content .= ob_get_contents();
	ob_end_clean();
}

//Set the page title
$pageTitle = "TEL Finance - Add an Account";

//Import the template
include_once("./template.php");

?>