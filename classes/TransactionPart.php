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

class TransactionPart {
	public $memo = "";
	public $amount = 0.0;
	public $categoryid = 0;
	public $category = "";
	public $transpartid = 0;
	
	function __toString() {
		$s = "TransPartID: " . $this->transpartid . "<br />";
		$s.= "Category: (" . $this->categoryid.") " . $this->category . "<br />";
		$s.= "Memo: " . $this->memo . "<br />";
		$s.= "Amount: " . $this->amount . "<br />";
		return $s;
	}
}