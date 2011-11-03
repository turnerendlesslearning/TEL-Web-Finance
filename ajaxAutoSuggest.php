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

<?

include_once(dirname(__FILE__) . "/includes/db.php");

$conn = dbConnect();

//Send some headers to keep the user's browser from caching the response.
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT" ); 
header("Last-Modified: " . gmdate( "D, d M Y H:i:s" ) . "GMT" ); 
header("Cache-Control: no-cache, must-revalidate" ); 
header("Pragma: no-cache" );
header("Content-Type: text/xml; charset=utf-8");

if(isset($_GET['getPayeesByLetters']) && isset($_GET['letters'])){
	$letters = $_GET['letters'];
	$letters = preg_replace("/[^a-z0-9 ]/si","",$letters);
	$query = "select payeeid, payeename from payee where payeename like '".$letters."%'";
	$res = selectData($query);
	//$res = mysql_query("select ID,countryName from ajax_countries where countryName like '".$letters."%'") or die(mysql_error());
	
	//#echo "1###select ID,countryName from ajax_countries where countryName like '".$letters."%'|";
	
	while($inf = getNextDataRow($res)){
		echo $inf["payeeid"]."###".$inf["payeename"]."|";
	}	
} else if(isset($_GET['getCategoriesByLetters']) && isset($_GET['letters'])){
	$letters = $_GET['letters'];
	$letters = preg_replace("/[^a-z0-9 ]/si","",$letters);
	$query = "select catid, catname from cat where catname like '".$letters."%'";
	$res = selectData($query);
	//$res = mysql_query("select ID,countryName from ajax_countries where countryName like '".$letters."%'") or die(mysql_error());
	
	//#echo "1###select ID,countryName from ajax_countries where countryName like '".$letters."%'|";
	
	while($inf = getNextDataRow($res)){
		echo $inf["catid"]."###".$inf["catname"]."|";
	}	
}
?>
