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
include_once(dirname(__FILE__) . "/classes/Category.php");

include_once(dirname(__FILE__) . "/includes/config.php");
include_once(dirname(__FILE__) . "/includes/security.php");
include_once(dirname(__FILE__) . "/includes/db.php");

setlocale(LC_MONETARY, 'en_US');

//Security Check
if (!isLoggedIn()) {
    $_SESSION = array();
    session_destroy();
}

//Build any headers
ob_start();
?>
<script type="text/javascript">
    function displayTrans(catid) {
        var allRows = document.getElementsByTagName("tr");
        for(var i = 0; i < allRows.length; i++) {
            var id = allRows[i].id;
            if(id.indexOf('transrow_' + catid + '_') == 0) {
                if(allRows[i].style.display != 'table-row') {
                    allRows[i].style.display='table-row';
                } else {
                    allRows[i].style.display='none';
                }
            }
        }
    }
</script>

<?php
$additionalHeaders .= ob_get_contents();
ob_end_clean();


//Get the account id
$reportType = constant($_GET['t']);


//Build the body
$content = "<h1>Report: $reportType</h1>" . PHP_EOL;
if (!isset($_SESSION['username'])) {
    ob_start();
    include("./login.php");
    $content .= ob_get_contents();
    ob_end_clean();
} else {
    $content .= "<a href=\"logout.php\">Logout</a> > <a href=\"./index.php\">Accounts</a> > Reports";

    //Start Building Page Content
    ob_start();
    
    

    function searchCats($catid, $cats) {
        $foundCatId = FALSE;
        for ($i = 0; $i < count($cats); $i++) {
            if ($cats[$i]->id == $catid) {
                return $i;
            }
        }
        return $foundCatId;
    }
    function outputReport($cats) {
        $masterTotal = 0;
        echo "<table class=\"tableStyle1\">" . PHP_EOL;
        foreach ($cats as $c) {
            echo $c . PHP_EOL;
            $masterTotal += $c->total;
        }
        echo "</table>" . PHP_EOL;
        echo "<p><span style=\"font-weight:bold;color:blue;\">$masterTotal</span></p>" . PHP_EOL;
    }
    function getReport($type, $parentCat = null) {

        //GET DATE RANGE
        $y = date("Y");
        $m = date("m");
        $d = "01";
        $startDate = "$y-$m-$d";
        $endDate = date("Y-m-d");

        if (isset($_GET['e'])) {
            $endDate = $_GET['e'];
        }
        if (isset($_GET['s'])) {
            $startDate = $_GET['s'];
        }

        // LOAD ALL THE CATEGORIES
        if($type == "IBC") {
            $inc = 1;
        } else {
            $inc = 0;
        }
        $cats = loadBudgetCategories($startDate, $endDate, $inc, null);
        return $cats;
        //outputReport($cats);
        //die();
/*
        $query = "";
        if ($type == "IBC") {
            $query = "SELECT * FROM trans, transparts, cat WHERE " .
                    "(transparts.transid = trans.transid) AND " .
                    "(cat.catid = transparts.catid) AND (cat.isincome = 1) ";
            $query .= " AND (transdate <= '$endDate') AND " .
                    "(transdate >= '$startDate') ";
            if($parentCat == null) {
                $query .= " AND (cat.catname NOT LIKE '%:%') ";
            } else {
                $query .= " AND (cat.catname LIKE '" . $parentCat . ":%') ";
            }
            $query .= "ORDER BY transdate ASC";
        } else {
            $query = "SELECT * FROM trans, transparts, cat WHERE " .
                    "(transparts.transid = trans.transid) AND " .
                    "(cat.catid = transparts.catid) AND (cat.isincome = 0) ";
            $query .= " AND (transdate <= '$endDate') AND " .
                    "(transdate >= '$startDate') ";
            if($parentCat == null) {
                $query .= " AND (cat.catname NOT LIKE '%:%') ";
            } else {
                $query .= " AND (cat.catname LIKE '" . $parentCat . ":%') ";
            }
            $query .= "ORDER BY transdate ASC";
        }
        $res = selectData($query);
        if(!is_object($res) || $res->num_rows == 0) {
            return null;
        }
        $cats = array();



        while ($row = getNextDataRow($res)) {
            $id = $row['catid'];
            $idInCats = searchCats($id, $cats);

            if ($idInCats !== FALSE) {
                //Add this data to the current pile
                $cats[$idInCats]->total += $row['amount'];
                $details = array($row['transdate'], getPayee($row['transpayee']), $row['memo'], $row['amount']);
                $cats[$idInCats]->parts[] = $details;
            } else {
                //Create a new Category object and add to the list with the prelim info
                $c = new Category();
                $c->id = $id;
                $c->name = getCategory($id);
                $c->total += $row['amount'];
                $c->budgeted = getBudgetedAmount($id, ((int) date("n") - 1));
                $details = array($row['transdate'], getPayee($row['transpayee']), $row['memo'], $row['amount']);
                $c->parts[] = $details;
                if ($row['prorate']) {
                    $c->prorated = true;
                } else {
                    $c->prorated = false;
                }
                $c->isincome = $row['isincome'];
                //Load Subcategories
                if($c->name != null) {
                    $c->subcategories = getReport($type, $c->name);
                }
                $cats[] = $c;
            }
        }

        sort($cats);
        return $cats;
 * 
 * 
 */
        
    }
    
    //getReport("IBC");
    //getReport("EBC");
    
    
    if ($_GET['t'] == "IBC") {
        echo "<h2>Income Report</h2>";
        $cats = getReport("IBC");
        outputReports($cats);
    } else if ($_GET['t'] == "EBC") {
        echo "<h2>Expense Report</h2>";
        $cats = getReport("EBC");
        outputReports($cats);
    } else {
        echo "<h2>Income Report</h2>";
        $cats = getReport("IBC");
        outputReport($cats);
        echo "<br />";
        echo "<h2>Expense Report</h2>";
        $cats = getReport("EBC");
        outputReport($cats);
    }
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