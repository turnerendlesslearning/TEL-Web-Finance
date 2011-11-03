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
$id = $_GET['tid'];

require_once("./includes/db.php");

$res = selectData("SELECT * FROM trans WHERE (transid = $id)");
$row = getNextDataRow($res);

//Send some headers to keep the user's browser from caching the response.
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT" ); 
header("Last-Modified: " . gmdate( "D, d M Y H:i:s" ) . "GMT" ); 
header("Cache-Control: no-cache, must-revalidate" ); 
header("Pragma: no-cache" );
header("Content-Type: text/xml; charset=utf-8");

$transid = $id;
$payeeid = $row['transpayee'];

echo "<?xml version=\"1.0\"?>" . PHP_EOL;
echo "<transaction>" . PHP_EOL;
echo "<transid>" . $id . "</transid>" . PHP_EOL;
echo "<payeeid>" . $payeeid . "</payeeid>" . PHP_EOL;
echo "<payeename>" . getPayee($payeeid) . "</payeename>" . PHP_EOL;
echo "<transdate>" . $row['transdate'] . "</transdate>" . PHP_EOL;
echo "<number>" . $row['transnumber'] . "</number>" . PHP_EOL;
echo "<typeid>" . $row['transtypeid'] . "</typeid>" . PHP_EOL;
echo "<type>" . getTransactionType($row['transtypeid']) . "</type>" . PHP_EOL;

$res = selectData("SELECT * FROM transparts WHERE (transid = $id)");
while($row = getNextDataRow($res)) {
	echo "<transactionpart>" . PHP_EOL;
	echo "<transpartid>" . $row['transpartid'] . "</transpartid>" . PHP_EOL;
	echo "<catid>" . $row['catid'] . "</catid>" . PHP_EOL;
	echo "<category>" . getCategory($row['catid']) . "</category>" . PHP_EOL;
	echo "<memo>" . $row['memo'] . "</memo>" . PHP_EOL;
	echo "<amount>" . $row['amount'] . "</amount>" . PHP_EOL;
	echo "</transactionpart>" . PHP_EOL;
}

echo "</transaction>" . PHP_EOL;
?>