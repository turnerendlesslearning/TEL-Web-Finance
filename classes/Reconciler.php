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
include_once("./classes/Transaction.php");

class Reconciler {
	
	public $accid = 0;
	public $sbalance = 0;
	public $ebalance = 0;
	public $reconid = 0;
	public $closeDate = NULL;
	public $transactions = NULL;
	
	function __construct($accountid) {
		$this->accid = $accountid;
		$this->getStartingBalance($accountid);
		$this->transactions = array();
	}
	
	/***
	 * Checks to see if there is a reconciliation in progress already
	 * @return mixed The reconid if a reconciliation is in progress, FALSE if there isn't one
	 */
	function isUnfinished() {
		$retVal = getUnfinishedReconId($this->accid);
		return $retVal;
	}
	function getStartingBalance() {
		$this->sbalance = getReconcileStartBalance($this->accid);
	}
	function getEndBalance() {
		$this->ebalance = getReconcileEndBalance($this->reconid);
	}
	function loadUnfinishedRec() {
		$this->reconid = $this->isUnfinished();
		//Need to load the recdate into the closedate
		$this->closeDate = getReconcileCloseDate($this->reconid);
		$this->getEndBalance();
	}
	function loadTransactions() {
		// Get all transactions that fit three criteria:
		// 1. This accountid
		// 2. Unreconciled OR
		// 3. Reconciled with this reconid
		
		$this->transactions = loadReconcileTransactions($this->accid, $this->reconid);
	}
}

?>