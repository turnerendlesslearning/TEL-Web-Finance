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
session_start();

include 'php-ofc-library/open-flash-chart.php';
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

//Default behavior... display last thirty days of transactions
$endDate = date("Y-m-d");
$startDate = date('-30 days', time());

if(isset($_SESSION['s'])) {
	$startDate = $_SESSION['s'];
}
if(isset($_SESSION['e'])) {
	$endDate = $_SESSION['e'];
}

$l = getLedger($_GET['id'], $startDate, $endDate);
//$l = getLedger(1, "2011-06-01", "2011-06-18");
$transcount = count($l->transactions);

$title = new title( date("D M d Y") );

$data = array();
$min = 0;
$max = 0;
$totalCountdown = $l->endBalance;
if($totalCountdown > $max) 
	$max = $totalCountdown;

$data[] = $totalCountdown;

foreach($l->transactions as $t) {
	//$totalCountdown -= $t->total;
	$totalCountdown = $t->balance;
	
	if($totalCountdown > $max)
		$max = $totalCountdown;
	if($totalCountdown < $min)
		$min = $totalCountdown;
	//$data[] = $totalCountdown;
	$data[] = (float) $totalCountdown;
}
$data = array_reverse($data);
$steps = (int)(($max - $min)/10);



























// ------- LINE 2 -----
$d = new solid_dot();
$d->size(3)->halo_size(1)->colour('#3D5C56');

$line = new line();
$line->set_default_dot_style($d);
$line->set_values( $data );
$line->set_width( 2 );
$line->set_colour( '#3D5C56' );


$bar = new bar_rounded_glass();
$bar->set_values( $data );

$chart = new open_flash_chart();
$chart->set_title( $title );
$chart->add_element( $line );

//
// create a Y Axis object
//
$y = new y_axis();
// grid steps:
$y->set_range( 0, $max, (int)($max / 10));

//
// Add the Y Axis object to the chart:
//
$chart->set_y_axis( $y );

echo $chart->toPrettyString();


?>
