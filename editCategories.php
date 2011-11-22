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

//Includes
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
$additionalHeaders = "";
ob_start();
?>
<script type="text/javascript">
var editCatFormHidden = true;
var editCatFormBasicText = "";
var submittingToggle = false;
function showEditCatForm(catid, catname, inc_or_exp) {
    if(submittingToggle) return;
    	if(editCatFormBasicText == "") {
		editCatFormBasicText = document.getElementById('editCatDiv').innerHTML;
		document.getElementById('editCatDiv').innerHTML = "";
	}

	if(editCatFormHidden) {
		//Display it
		document.getElementById('messageBox').innerHTML = editCatFormBasicText;
                document.editCatForm.catname.value = catname;
                if(inc_or_exp == 'income') {
                    document.getElementById("inc_radio").checked = true;
                } else {
                    document.getElementById('exp_radio').checked = true;
                }
                document.editCatForm.catid.value = catid;
		document.getElementById('tintedPane').style.display = 'block';
		document.getElementById('messageBoxHolder').style.display = 'block';
		editCatFormHidden = false;
		document.editCatForm.action="editCategoryProcess.php";
	} else {
		//Hide it
		document.getElementById('messageBox').innerHTML = '';
		document.getElementById('tintedPane').style.display = 'none';
		document.getElementById('messageBoxHolder').style.display = 'none';
		editCatFormHidden = true;
		splitRows = 1;
	}
}
function validateEditCat() {
    if(document.editCatForm.merge_cat.selectedIndex != 0) {
        var result = confirm("Note: Merging categories will cause this " +
            "category to disappear.  All transactions using " +
            "this category will be changed to the target category. " +
            "Any changes in this form to the name or the expense-income " +
            "will be ignored.  Those changes will need to be made with the " +
            "target category.\n\n\n**This cannot be undone**\n\n"+
            "Are you sure?");
        
        if(result) {
            document.editCatForm.submit();
        }
    } else {
        document.editCatForm.submit();
    }
    
}
function toggleProrate(catid, val) {
    submittingToggle = true;
    document.toggleProrateForm.catid.value = catid;
    document.toggleProrateForm.prorate.value = val;
    document.toggleProrateForm.submit();
}
</script>


<?php
$additionalHeaders .= ob_get_contents();
ob_end_clean();

//Build the body
$content = "<h1>TEL Finance 2011</h1>" . PHP_EOL;
if(!isset($_SESSION['username'])) {
	ob_start();
	include("./login.php");
	$content .= ob_get_contents();
	ob_end_clean();
} else {
	$content .= "<a href=\"logout.php\">Logout</a> > <a href=\"index.php\">Accounts</a> > Categories";
	
	//Start Building Page Content
	ob_start();
	?>

        <div id="messageBoxHolder" class="messageBoxHolder">
        <div id="messageBox" class="messageBox"></div>
        </div>
        <div id="editCatDiv">
        <form name="editCatForm" method="post" action="editCategoryProcess.php">
            <input type="hidden" name="catid" />
            <input type="text" name="catname" /><br />
            <input type="radio" id="inc_radio" name="inc_or_exp" value="income" /> income <br />
            <input type="radio" id="exp_radio" name="inc_or_exp" value="expense" /> expense <br />
            Merge with Category: <select name="merge_cat">
                <option value="">-none-</option>
                <?php
                $cat = getCategories();
                while($row = getNextDataRow($cat)) {
                    echo "<option value=\"" . $row['catid'] . 
                            "\">" . $row['catname'] . "</option>" . PHP_EOL;
                }
                ?>
            </select><br />
            <input type="button" value="Submit" onclick="validateEditCat();"/> 
            <input type="button" value="Cancel" onclick="showEditCatForm();" />
        </form>
        </div>

        <!-- INCOME CATEGORIES -->
	<div id="incomeDiv">
	<table class="tableStyle1">
	<tr class="header"><td>Income Categories</td><td>Prorate Budget?</td></tr>
	<?php 
	$cat = getCategories("INCOME");
	$counter = 0;

	while($row = getNextDataRow($cat)) {
		echo "<tr";
		if($counter % 2) {
			echo " class=\"odd\"";
		}
                echo " onclick=\"showEditCatForm(".$row['catid'] . ",'".
                        $row['catname']."', 'income');\"";
		echo "><td>" . $row['catname'] . "</td>";
                echo "<td><input type=\"checkbox\" name=\"prorated".
                        $row['catid']."\" ";
                if($row['prorate']) {
                    echo "checked";
                }
                echo " onchange=\"toggleProrate(".$row['catid'].", ";
                if($row['prorate']) {
                    echo "0";
                } else {
                    echo "1";
                }
                echo ");\"";
                echo "/></td>" . PHP_EOL;
                echo "</tr>" . PHP_EOL;
		$counter++;
	}
	?>
	</table>
	</div>
        <br />

	<!-- EXPENSE CATEGORIES -->
	<div id="expensesDiv">
	<table class="tableStyle1">
	<tr class="header"><td>Expense Categories</td><td>Prorate Budget?</td></tr>
	<?php 
	$cat = getCategories("EXPENSES");
	$counter = 0;

	while($row = getNextDataRow($cat)) {
		echo "<tr";
		if($counter % 2) {
			echo " class=\"odd\"";
		}
                echo " onclick=\"showEditCatForm(".$row['catid'] . ",'".
                        $row['catname']."', 'expense');\"";
		echo "><td>" . $row['catname'] . "</td>";
                echo "<td><input type=\"checkbox\" name=\"prorated".
                        $row['catid']."\" ";
                if($row['prorate']) {
                    echo "checked";
                }
                echo " onchange=\"toggleProrate(".$row['catid'].", ";
                if($row['prorate']) {
                    echo "0";
                } else {
                    echo "1";
                }
                echo ");\"";
                echo "/></td>" . PHP_EOL;
                echo "</tr>" . PHP_EOL;
		$counter++;
	}
	?>
	</table>
	</div>
	<div id="tintedPane" class="tintedPane"></div>
        <form name="toggleProrateForm" method="post" action="toggleProratedProcess.php">
            <input type="hidden" name="catid" />
            <input type="hidden" name="prorate" />
        </form>
	<?php
	$content .= ob_get_contents();
	ob_end_clean();
}

//Set the page title
$pageTitle = "TEL Finance - Edit Categories";

//Import the template
include_once("./template.php");

?>
