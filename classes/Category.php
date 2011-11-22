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
 *
 */
class TransPart {
        public $memo;
        public $amount;
        public $payee;
        public $tpid;
        public $date;
    }
    
class Category {

    public $name = "";
    public $prorated;
    public $id = 0;
    public $total = 0.0;
    public $localTotal = 0.0;
    public $budgeted = 0.0;
    //public $parts;
    public $subcategories;
    public $transactionParts;
    public $isincome;
    public $parent = null;
    
    function addMoneyToParent($total, $budgeted) {
        if(!is_object($this->parent)) { return; }
        
        $c = $this->parent;
        $c->total += $total;
        $c->budgeted += $budgeted;
        if($c->parent != null) {
            $c->addMoneyToParent($total, $budgeted);
        }
    }
    
    

    function __construct($catid) {
        $this->id = $catid;
        $transactionParts = array();
        $subcategories = array();
        
        
        //$parts = array();
        
    }
    function loadTransactions($startdate, $enddate) {
        $query = "SELECT * FROM payee, trans, transparts, cat WHERE " . 
                "(trans.transpayee = payee.payeeid) AND " .
                "(trans.transid = transparts.transid) AND ".
                "(trans.transdate >= '$startdate') AND ".
                "(trans.transdate <= '$enddate') AND ".
                "(cat.catid = transparts.catid) AND ".
                "(cat.catid = $this->id)";
        $result = selectData($query);
        if(!is_object($result) || ($result->num_rows == 0)) {
            return;
        }
        
        while($row = $result->fetch_assoc()) {
            $tp = new TransPart();
            $tp->payee = $row['payeename'];
            $tp->memo = $row['memo'];
            $tp->amount = $row['amount'];
            $tp->date = $row['transdate'];
            $tp->tpid = $row['transpartid'];
            $this->transactionParts[] = $tp;
            
            //Update Totals for the Category
            $this->total += $tp->amount;
        }
        $this->localTotal = $this->total;
    }

    function __toString() {

        //OUTPUT THE HEADER
        $s = "<tr class=\"header\" onclick=\"displayTrans(" .
                $this->id .
                ");\">";
        $s .= "<td>$this->name</td>";
        $s .= "<td colspan=\"3\"></td>" . PHP_EOL;
        $s .= "<td>BUDGETED: $" . money_format("%n", $this->budgeted) . "</td>" . PHP_EOL;

        $s .= "</tr>" . PHP_EOL;

        //OUTPUT THE TRANSACTIONS
        if(count($this->transactionParts) > 0) {
            foreach ($this->transactionParts as $tp) {
                $s .= "<tr id=\"transrow_" . $this->id . "_" . $tp->tpid;
                $s .= "\" class=\"budgetTransRow\"><td></td><td>" . $tp->date;
                $s .= "</td><td>" . $tp->date . "</td><td>";
                $s .= $tp->memo . "</td><td>$";
                $s .= money_format('%n', $tp->amount);
                $s .= "</td></tr>" . PHP_EOL;
            }
        }

        // OUTPUT THE TOTAL
        $s .= "<tr><td colspan=\"4\"></td><td class=\"total_cell\"><i>total</i>: $";
        $s .= "<span class=\"";
        if ($this->isincome) {
            if ($this->total > $this->budgeted) {
                $s .= "green";
            } else {
                $s .= "black";
            }
        } else {
            if ($this->total < $this->budgeted) {
                $s .= "red";
            } else {
                $s .= "black";
            }
        }
        $s .= "\">";
        $s .= money_format("%n", $this->total);
        if($this->total != $this->localTotal) {
            $s .= " (" . 
                money_format("%n", $this->localTotal) . ")";
        }
        $s .= "</span></td></tr>";
        $s .= PHP_EOL;

        //OUTPUT THE REMAINING BUDGETED AMOUNT
        $remaining = -((int) ($this->budgeted - $this->total));
        $s .= "<tr><td colspan=\"4\"></td>";
        $s .= "<td class=\"total_cell\"><i>remaining</i>: $";
        $s .= "<span class=\"";
        if ($this->isincome) {
            $s .= "black";
        } else {
            if ($remaining < 0) {
                $s .= "red";
            } else {
                $s .= "black";
            }
        }
        $s .= "\">";
        $s .= money_format("%n", $remaining);
        $s .= "</span>";
        $s .= "</td></tr>" . PHP_EOL;

        //OUTPUT PERCENT USED OF BUDGET CATEGORY
        if ($this->budgeted != 0) {

            //OUTPUT TOTAL PERCENT
            $per = ((float) ($this->total / $this->budgeted)) * 100;
            $s .= "<tr><td colspan=\"4\"></td><td class=\"total_cell\">";
            $s .= "<span class=\"";
            if ($this->isincome) {
                $s .= "black";
            } else {
                if ($per > 100) {
                    $s .= "red";
                } else {
                    $s .= "black";
                }
            }
            $s .= "\">";
            $s .= sprintf("%d", $per);
            $s .= "</span>";
            $s .= "%</td></tr>" . PHP_EOL;

            //OUTPUT PERCENT PRORATED BY TIME OF THE MONTH
            if ($this->prorated) {
                $per = ($this->total / (($this->budgeted / (int) date("t")) * date("j"))) * 100;
                $s .= "<tr><td colspan=\"4\"></td>";
                $s .= "<td class=\"total_cell\">";
                $s .= "<span class=\"";
                if ($this->isincome) {
                    $s .= "black";
                } else {
                    if ($per > 100) {
                        $s .= "red";
                    } else {
                        $s .= "black";
                    }
                }
                $s .= "\">";
                $s .= sprintf("%d", $per);
                $s .= "</span>";
                $s .= "% <i>(prorated)</i></td></tr>" . PHP_EOL;
            }
        }
        if(is_array($this->subcategories)) {
            foreach($this->subcategories as $sub) {
                $s .= $sub->__toString();
            }
        }
        return $s;
    }

}

?>
