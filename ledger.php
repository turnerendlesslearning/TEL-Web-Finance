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
$additionalHeaders = "<script language=\"JavaScript\" src=\"./tigra_calendar/calendar_us.js\"></script>";
$additionalHeaders .= "<link rel=\"stylesheet\" href=\"./tigra_calendar/calendar.css\">";
ob_start();
?>
<script type="text/javascript">
var newTransFormHidden = true;
var newTransFormBasicText = "";

function deleteTransaction() {
	var result = confirm("Are you sure you want to delete this transaction?");
	if(!result) {
		return;
	}

	document.newTransForm.action = 'deleteTransactionProcess.php';
	document.newTransForm.submit();
}

function ajaxGetTransaction(transid) {
	if (window.XMLHttpRequest) { // code for IE7+, Firefox, Chrome, Opera, Safari
	  	xmlhttp=new XMLHttpRequest();
	} else { // code for IE6, IE5
	 	xmlhttp=new ActiveXObject("Microsoft.XMLHTTP");
	}

	xmlhttp.onreadystatechange=function() {
		if (xmlhttp.readyState==4 && xmlhttp.status==200) {
	    	x=xmlhttp.responseXML.childNodes(0);
			var transid = x.getElementsByTagName("transid")(0).childNodes(0).nodeValue;
			var payeeid = x.getElementsByTagName("payeeid")(0).childNodes(0).nodeValue;
			
			var payeename = "";
			if(x.getElementsByTagName("payeename")(0).childNodes.length > 0) {
				payeename = x.getElementsByTagName("payeename")(0).childNodes(0).nodeValue;
			}
			var transdate = x.getElementsByTagName("transdate")(0).childNodes(0).nodeValue;
			var number = x.getElementsByTagName("number")(0).childNodes(0).nodeValue;
			var typeid = x.getElementsByTagName("typeid")(0).childNodes(0).nodeValue;
			var type = x.getElementsByTagName("type")(0).childNodes(0).nodeValue;

			document.newTransForm.transid.value = transid;
			document.newTransForm.transdate.value = transdate;
			document.newTransForm.transpayee.value = payeename;
			document.newTransForm.transnumber.value = number;
			document.newTransForm.ttype.value = typeid;

			if(payeename=='transfer') {
				transChanged();
				document.newTransForm.transnumber.value = "";
				document.newTransForm.transferAccount.value = number;
			}

			//Begin to build the transaction parts
			var parts = x.getElementsByTagName("transactionpart");
			for(var i = 0; i < parts.length; i++) {
				if(i > 0) {
					//add the extra cat, mem, and amo fields
					addSplitRow();				
				}

				var catid = parts(i).getElementsByTagName("catid")(0).childNodes(0).nodeValue;
				var category = parts(i).getElementsByTagName("category")(0).childNodes(0).nodeValue;
				var memo = "";
				try {
					memo = parts(i).getElementsByTagName("memo")(0).childNodes(0).nodeValue;
				} catch (err) {
				}
				var amount = parts(i).getElementsByTagName("amount")(0).childNodes(0).nodeValue;

				document.getElementById('cat' + (i + 1)).value = category;
				document.getElementById('mem' + (i + 1)).value = memo;
				document.getElementById('amo' + (i + 1)).value = amount;
			}
	    	
	    }
	};
	xmlhttp.open("GET","ajaxGetTransaction.php?tid=" + transid,true);
	xmlhttp.send();
}

function editTransaction(transid) {
	if(newTransFormBasicText == "") {
		newTransFormBasicText = document.getElementById('newTransDiv').innerHTML;
		document.getElementById('newTransDiv').innerHTML = "";
	}

	if(newTransFormHidden) {
		//Display it
		document.getElementById('messageBox').innerHTML = newTransFormBasicText;
		var d=new Date();
		var ds = d.getFullYear() + "-" + (d.getMonth() + 1) + "-" + d.getDate();
		document.newTransForm.transdate.value = ds;
		document.getElementById('tintedPane').style.display = 'block';
		document.getElementById('messageBoxHolder').style.display = 'block';
		newTransFormHidden = false;

		document.newTransForm.action="editTransactionProcess.php";
		document.getElementById("addTransactionButton").value = "Update Transaction";
		document.getElementById('deleteTransactionButton').style.display = 'block';

		
		//Load Transaction and Fill the Form with it
		ajaxGetTransaction(transid);
		
	}
}

function modDate(evt) {
	var c = String.fromCharCode(evt.charCode);
	if(c == '+') {
		var d = new Date(document.newTransForm.transdate.value);
		d.setDate(d.getDate() + 1);
		document.newTransForm.transdate.value = d.getFullYear() + "-" + (d.getMonth() + 1) + "-" + d.getDate();
		return false;
	} else if (c == '-') {
		d = new Date(document.newTransForm.transdate.value);
		d.setDate(d.getDate() - 1);
		document.newTransForm.transdate.value = d.getFullYear() + "-" + (d.getMonth() + 1) + "-" + d.getDate();
		return false;
	} else {
		return true;
	}
}

function transChanged() {
	if(document.newTransForm.ttype.options[document.newTransForm.ttype.selectedIndex].text == 'transfer') {
		//Display to account selector in place of the payee field
		document.getElementById('transpayee').style.display='none';
		document.getElementById('payeeLabel').innerHTML = 'Receiving Account';
		document.getElementById('transferAccount').style.display = 'block';
		document.getElementById('transpayee').value = 'transfer';
	} else {
		//Check to see if the payee selector is visible... if not, enable it
		//Need to check this because we might be switching from debit to check for instance
		//And there would be no need to change anything
		if(document.getElementById('payeeLabel').innerHTML != 'Payee') {
			document.getElementById('payeeLabel').innerHTML = "Payee";
			document.getElementById('transferAccount').style.display = 'none';
			document.getElementById('transpayee').style.display = 'block';
			document.getElementById('transpayee').value = '';
		}
	}
}

function showNewTransForm() {
	if(newTransFormBasicText == "") {
		newTransFormBasicText = document.getElementById('newTransDiv').innerHTML;
		document.getElementById('newTransDiv').innerHTML = "";
	}

	if(newTransFormHidden) {
		//Display it
		document.getElementById('messageBox').innerHTML = newTransFormBasicText;
		//document.getElementById('newTransDiv').innerHTML = '';
		var d=new Date();
		var ds = d.getFullYear() + "-" + (d.getMonth() + 1) + "-" + d.getDate();
		document.newTransForm.transdate.value = ds;
		document.getElementById('tintedPane').style.display = 'block';
		document.getElementById('messageBoxHolder').style.display = 'block';
		newTransFormHidden = false;

		document.newTransForm.action="addTransactionProcess.php";
	} else {
		//Hide it
		//document.getElementById('newTransDiv').innerHTML = document.getElementById('messageBox').innerHTML;
		document.getElementById('messageBox').innerHTML = '';
		document.getElementById('tintedPane').style.display = 'none';
		document.getElementById('messageBoxHolder').style.display = 'none';
		newTransFormHidden = true;
		splitRows = 1;
	}
}

var splitRows = 1;

function addSplitRow() {
	var t = document.getElementById('splitTable');
	var lastRow = t.rows.length;
	var newRow = t.insertRow(lastRow - 1);

	splitRows++;

	newRow.id = 'split' + splitRows;

	var catCell = newRow.insertCell(0);

	var catMin = document.createElement('input');
	catMin.type = 'button';
	catMin.value = "-";
	catMin.id = splitRows;
	
	catMin.onclick=function (evt) {
		document.getElementById('splitTable').childNodes[1].removeChild(document.getElementById('split' + evt.target.id));
		updateTotal();
	};
	catCell.appendChild(catMin);
	
	
	var catEl = document.createElement('input');
	catEl.type = 'text';
	catEl.name = 'cat' + splitRows;
	catEl.id = 'cat' + splitRows;
	catEl.size = 25;
	catEl.setAttribute('autocomplete', 'off');
	catEl.onkeyup = function (evt) {
		ajax_showOptions(evt.target, 'getCategoriesByLetters', evt);
	};
	catCell.appendChild(catEl);
	var hiddenEl = document.createElement('input');
	hiddenEl.type = 'hidden';
	hiddenEl.name = 'cat' + splitRows + '_hidden';
	hiddenEl.id = 'cat' + splitRows + '_ID';
	catCell.appendChild(hiddenEl);
	
	var memCell = newRow.insertCell(1);
	var memEl = document.createElement('input');
	memEl.type = 'text';
	memEl.name = 'mem' + splitRows;
	memEl.id = 'mem' + splitRows;
	memEl.size = 25;
	memCell.appendChild(memEl);

	var amoCell = newRow.insertCell(2);
	var amoEl = document.createElement('input');
	amoEl.type = 'text';
	amoEl.name = 'amo' + splitRows;
	amoEl.id = 'amo' + splitRows;
	amoEl.size = 10;
	amoEl.onkeyup = function (evt) {
		updateTotal();
	};
	amoCell.appendChild(amoEl);
	
	
	
}

function updateTotal() {
	var el=document.getElementsByTagName("input");
	var total = 0.0;
    for(var i=0; i<el.length; i++) {
        if(el[i].hasAttribute('id')) {
            if(el[i].id.indexOf('amo') == 0) {
				if(el[i].value == "") {
					continue;
				}
				
            	var val = parseFloat(el[i].value, 10);

                if(!isNaN(val)) {
                    total += Math.round(val*100)/100;
                } else {
                	document.getElementById('totalSpan').innerHTML = '???';
                	return;
                }
            }
        }
    }

    var strTotal = Math.round(total*100)/100 + '';
    if(strTotal.lastIndexOf(".") == -1) {
        strTotal += ".00";
    } else if (strTotal.lastIndexOf(".") == strTotal.length - 2) {
        strTotal += "0";
    }
    document.getElementById('totalSpan').innerHTML = "$" + strTotal;
}

function showImport() {
	document.getElementById('importDiv').style.display = "";
}
</script>
<script
	type="text/javascript" src="js/ajax.js"></script>
<script
	type="text/javascript" src="js/ajax-dynamic-list.js"></script>
<?php
$additionalHeaders .= ob_get_contents();
ob_end_clean();


//Get the account id
$aid = $_GET['id'];

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
	$content .= "<a href=\"logout.php\">Logout</a> > <a href=\"./index.php\">Accounts</a> > Ledger";

	
	//fix();
	
	//Start Building Page Content
	ob_start();
	?>
<div id="ledgerDiv" class="contentDiv">
<input type="button" value="-new transaction-" onclick="showNewTransForm();" /> 
<input type="button" value="-reconcile account-" onclick="document.location.href='reconcile.php?id=<?php echo $_GET['id'];?>';" />
<input type="button" value="-chart balance-" onclick="document.location.href='chartBalance.php?id=<?php echo $_GET['id'];?>';" />
<input type="button" value="-import transactions-" onclick="showImport();" />
<div id="importDiv" style="display:none;border:1px solid black; padding:10px;width:500px;margin:5 0 5 0;">
<form enctype="multipart/form-data" name="importForm" method="post" action="preview_import.php">
Select CSV File: <input type="file" name="files" />
<input type="hidden" name="accountid" value="<?php echo $aid;?>" />
<input type="submit" value="Preview" />
</form>
</div>
<div id="messageBoxHolder" class="messageBoxHolder">
<div id="messageBox" class="messageBox"></div>
</div>

<div id="newTransDiv">
<form name="newTransForm" method="post"
	action="addTransactionProcess.php"><input type="hidden" name="accid"
	value="<?php echo $aid; ?>" /><input type="hidden" name="transid" />
<table class="tableStyle2">
	<!-- Header Row -->
	<tr class="header">
		<td>Date</td>
		<td>Type</td>
		<td>Number</td>
	</tr>
	</tr>
	
	
	<!-- Field Row -->
	<tr>
		<!-- TRANS DATE SELECTOR -->
		<td><script type="text/javascript">
		new tcal ({
			// form name
			'formname': 'newTransForm',
			// input name
			'controlname': 'transdate'
		});
	</script><input type="text" size="10" name="transdate"
			onkeypress="return modDate(evt);" /></td>

		<!-- TRANS TYPE SELECTOR -->
		<td><select name="ttype" onchange="transChanged();">
		<?php
		$types = getTransactionTypes();
		while($type = getNextDataRow($types)) {
			echo "<option value=\"".$type['transtypeid'] . "\"";
			if($type['transtype'] == 'debit') {
				echo " selected";
			}
			echo ">" . $type['transtype'] . "</option>" . PHP_EOL;
		}
		?>
		</select></td>

		<!-- CHECK NUMBER INPUT -->
		<td><input type="text" name="transnumber" size="4" /></td>
	</tr>
	
	<!-- HEADER ROW -->
	<tr>
		<td colspan="3"><span id="payeeLabel">Payee</span></td>
	</tr>
	
	<!-- FIELD ROW -->
	<tr>
		<td colspan="3">
			<!-- PAYEE -->
			<select name="transferAccount" id="transferAccount">
			<?php
			$res = selectData("SELECT * FROM acc WHERE (accid <> $aid)");
			while($row = getNextDataRow($res)) {
				echo "<option value=\"" . $row['accid'] . "\">" . $row['accname'] . "</option>" . PHP_EOL;
			}
			?>
			</select> <input type="text" name="transpayee" id="transpayee"
				size="35"
				onkeyup="ajax_showOptions(this, 'getPayeesByLetters', event);"
				autocomplete="off" /> <input type="hidden" id="transpayee_hidden"
				name="transpayee_ID">
		</td>
	</tr>




	<!-- PUT THE SPLIT INFO HERE -->
	<tr>
		<td colspan="3">
		<table id="splitTable" class="tableStyle1"
			style="float: left; margin-top: 10px;">
			<tr class="footer">
				<td colspan="2">Category</td>
			</tr>
			<tr id="split1">
				<td colspan="2"><input type="button" id="plusButton" value="+"
					onclick="addSplitRow();" /><input type="text" size="25" name="cat1"
					id="cat1"
					onkeyup="ajax_showOptions(this, 'getCategoriesByLetters', event);"
					autocomplete="off" /><input type="hidden" id="cat1_hidden"
					name="cat1_ID"></td>
			</tr>
			<tr class="footer">
				<td colspan="2">Memo</td>
			</tr>
			<tr id="split1">
				<td colspan="2"><input type="text" size="25" name="mem1" id="mem1" /></td>
			</tr>
			
			
			
			<tr class="footer">
				<td colspan="2">Amount</td>
			</tr>
			
			<tr id="split1">
				<td colspan="2"><input type="text" size="10" name="amo1" id="amo1"
					onkeyup="updateTotal();" /></td>
			</tr>
			

			<tr class="footer">
				<td id="updateTotalCell"><input type="button" value="updateTotal =>"
					onclick="updateTotal();" /></td>
				<td id="totalSpanCell">Total: <span id="totalSpan">$0.00</span></td>
			</tr>
		</table>
		</td>

</table>
<input type="button" value="Cancel" onclick="showNewTransForm();" /> <input
	type="submit" value="Add Transaction" id="addTransactionButton" />
	<input type="button" value="Delete Transaction" id="deleteTransactionButton" onclick="deleteTransaction();" /></form>
</div>





</div>



<div id="transactionsDiv"><?php
//Default behavior... display last seven days of transactions
$endDate = date("Y-m-d");
$startDate = date("Y-m-d", strtotime("-15 days"));

if(isset($_GET['s'])) {
	$startDate = $_GET['s'];
}
if(isset($_GET['e'])) {
	$endDate = $_GET['e'];
}

$l = getLedger($aid, $startDate, $endDate);
$l->displayLedger();


?></div>


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
