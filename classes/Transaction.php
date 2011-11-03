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
require_once("./includes/db.php");
include_once("./classes/TransactionPart.php");

class Transaction {
	public $transid;
	public $total;
	public $payeeid;
	public $payee;
	public $tdate;
	public $number;
	public $typeid;
	public $type;
	public $parts;
	public $accountid;
	public $totalIncome = 0;
	public $totalExpense = 0;
	public $reconid;
	public $balance;
	
	function __construct() {
		$splits = array();
	}

	function calcTotal() {
		if(!is_array($this->parts)) {
			$this->total = 0;
			return $this->total;
		}
		
		for($i = 0; $i < count($this->parts); $i++) {
			$a = $this->parts[$i]->amount;
			$this->total += $a;
			if($a < 0) {
				$this->totalExpense += $a;
			} else {
				$this->totalIncome += $a;
			}
		}
		return $this->total;
	}
	
	function __toString() {
		$s = "Account ID: " . $this->accountid . "<br />";
		$s .= "Transaction ID: " . $this->transid . "<br />";
		$s .= "Payee ID: " . $this->payeeid . "<br />";
		$s .= "Type ID: " . $this->typeid . "<br />";
		$s .= "Date: " . $this->tdate . "<br />";
		$s .= "Type: " . $this->type . "<br />";
		$s .= "Payee: " . $this->payee . "<br />";
		$s .= "Number: " . $this->number . "<br />";

		$s .= "<br />";
		
		if(is_array($this->parts)) {
			for($i = 0; $i < count($this->parts); $i++) {
				$s .= $this->parts[$i] . "<br />";
			}
		}		

		return $s;
	}
	
	function getTotal() {
		return $this->total;
	}
	
	public function getYMD() {
		if($this->tdate) {
			return date("Y-m-d", $this->tdate);
		} else {
			return FALSE;
		}
		
	}
	
	
	
	
}

?>