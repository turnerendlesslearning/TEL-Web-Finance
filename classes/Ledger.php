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

include_once("./classes/Transaction.php");

class Ledger {
	public $accountid = 0;
	public $transactions = null;
	public $startDate = null;
	public $endDate = null;
	public $startBalance = 0;
	public $endBalance = 0;
	public $subStartBalance = 0;
	public $totalInflows = 0;
	public $totalOutflows = 0;
	public $total = 0;
	
	function __construct() {
		$transactions = array();
		
		
	}
	function getHighestAmount() {
		$totalCountdown = $this->endBalance;
		$h = $totalCountdown;
		foreach($this->transactions as $t) {
			$totalCountdown -= $t->total;
			if($totalCountdown > $h) 
				$h = $totalCountdown;
		}
		return $h;
	}
	function getLowestAmount() {
		$totalCountdown = $this->endBalance;
		$l = $totalCountdown;
		foreach($this->transactions as $t) {
			$totalCountdown -= $t->total;
			if($totalCountdown < $l) 
				$l = $totalCountdown;
		}
		return $l;	
	}
	
	function calcIncomeAndExpenses() {
		foreach($this->transactions as $t) {
			$this->totalInflows += $t->totalIncome;
			$this->totalOutflows += $t->totalExpense;
		}
	}
	function displayLedger() {
		echo "<table class=\"ledgerHeader\">";
		echo "<tr><td><b>Starting Balance on " . $this->startDate . "</b>:</td><td class=\"moneyCell\">$" . money_format("%n", $this->subStartBalance) ."</td></tr>";
		echo "<tr><td><b>Ending Balance on " . $this->endDate . "</b>:</td><td class=\"moneyCell\">$" . money_format("%n", $this->endBalance) . "</td></tr>";
		echo "<tr><td><b>Total Inflows</b>:</td><td class=\"moneyCell\">$" . money_format("%n", $this->totalInflows) . "</td></tr>";
		echo "<tr><td><b>Total Outflows</b>:</td><td class=\"moneyCell\">$" . money_format("%n", $this->totalOutflows) . "</td></tr>";
		echo "<tr><td><b>Net Income versus Expenses</b>:</td><td class=\"moneyCell\">$" . money_format("%n", $this->total) . "</td></tr>";
		echo "</table>". PHP_EOL;
		
		$totalCountdown = $this->endBalance;
		echo "<table class=\"tableStyle1\">";
		echo "<tr class=\"header\"><td>Date</td><td>Payee</td><td>R</td><td>Amount</td><td>Balance</td></tr>";
		foreach($this->transactions as $t) {
			echo "<tr onclick=\"editTransaction(".$t->transid.");\">";
			echo "<td>" . date("Y-m-d", strtotime($t->tdate)) . "</td>";
			echo "<td>" . $t->payee . "</td>";
			echo "<td>";
			if($t->reconid) {
				echo "<span style=\"color:green;\">R</span>";
			}
			echo "</td>";
			echo "<td class=\"moneyCell\">$" . money_format("%n", $t->total) . "</td>";
			
			//$balThusFar = $totalCountdown;
			//$query = "UPDATE trans SET balance=$totalCountdown WHERE transid=$t->transid";
			//require_once("./includes/db.php");
			//updateData($query);
			
			//echo "<td class=\"moneyCell\">$" . money_format("%n", $totalCountdown) . "</td>";
			echo "<td class=\"moneyCell\">$" . money_format("%n", $t->balance) . "</td>";
			//$totalCountdown -= $t->total;
			echo "</tr>";
		}
		echo "</table>";
		
		echo "<p>" . count($this->transactions) . " transaction(s)</p>";
	}
	
}