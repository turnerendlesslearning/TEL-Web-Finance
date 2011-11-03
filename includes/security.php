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
function isLoggedIn() {
	include_once("./includes/db.php");
	
	//First layer of security... check for the username in the session variable
	if(!isset($_SESSION['username'])) {
		//echo "username not set";
		return FALSE;
	}
	
	//Second layer... make sure the username is real and matches its uniqueid
	if(!$mysqli = dbConnect()) {
		return FALSE;
	} else {
		$query = "SELECT * FROM users WHERE (username='".$_SESSION['username']."')";
		$result = $mysqli->query($query);
		if($result) {
			$row = $result->fetch_assoc();
			$uuid = $row['uniqueid'];
			if($uuid != $_SESSION['uuid']) {
				return FALSE;
			}
		} else {
			//Couldn't find this user
			return FALSE;
		}
	}	
	return TRUE;

}