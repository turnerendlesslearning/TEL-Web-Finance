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

/* This is the path to the MySql database login information.
 * For security purposes, it is best to keep these out of the webroot
 * (in other words, on the server, but above the /www directory
 * It can reside anywhere, however.
 */
include_once("../../remoteSecurityFinance.php");



define("EBC", "Expenses By Category");
define("IBC", "Income By Category");
define("IEBC", "Income and Expenses by Category");

?>