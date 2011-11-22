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
include_once(dirname(__FILE__) . "/includes/config.php");
include_once(dirname(__FILE__) . "/includes/security.php");
include_once(dirname(__FILE__) . "/includes/db.php");

//Security Check
if(!isLoggedIn()) {
	$_SESSION = array();
	session_destroy();
}

if(!isset($_SESSION['username'])) {
	header("Location:/index.php");
} else {
    $catid = safe($_POST['catid']);
    $catname = safe($_POST['catname']);
    $inc_or_exp = safe($_POST['inc_or_exp']);
    $merge_cat = safe($_POST['merge_cat']);
    if($inc_or_exp == "income") {
        $inc_or_exp = 1;
    } else {
        $inc_or_exp = 0;
    }
    
    if($merge_cat != "") {
        //Handle a merge and then ignore all else, then return to cat page
        mergeCategories($catid, $merge_cat);
    } else {
        //Handle a change of category
        $query = "UPDATE cat SET catname='$catname', isincome=$inc_or_exp " .
                " WHERE catid=$catid";
        updateData($query);
        //echo $query . "<br />";
    }
		
	
    
    //die("HERE");
}

header("Location:./editCategories.php");
?>