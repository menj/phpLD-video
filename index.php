<?php 
/*#################################################################*\
|# Licence Number 029O-0000-02T0-0200
|# -------------------------------------------------------------   #|
|# Copyright (c)2023 PHP Link Directory.                           #|
|# http://www.phplinkdirectory.com                                 #|
\*#################################################################*/
	 
/*#################################################################*\
|# Licence Number 0MWJ-0125-116F-0214
|# -------------------------------------------------------------   #|
|# Copyright (c)2014 PHP Link Directory.                           #|
|# http://www.phplinkdirectory.com                                 #|
\*#################################################################*/
	 
/**
 # ################################################################################
 # Project:   PHP Link Directory
 #
 # **********************************************************************
 # Copyright (C) 2004-2013 NetCreated, Inc. (http://www.netcreated.com/)
 #
 # This software is for use only to those who have purchased a license.
 # A license must be purchased for EACH installation of the software.
 #
 # By using the software you agree to the terms:
 #
 #    - You may not redistribute, sell or otherwise share this software
 #      in whole or in part without the consent of the the ownership
 #      of PHP Link Directory. Please contact david@david-duval.com
 #      if you need more information.
 #
 #    - You agree to retain a link back to http://www.phplinkdirectory.com/
 #      on all pages of your directory if you purchased any of our "link back" 
 #      versions of the software.
 #
 #
 # In some cases, license holders may be required to agree to changes
 # in the software license before receiving updates to the software.
 # **********************************************************************
 #
 # For questions, help, comments, discussion, etc., please join the
 # PHP Link Directory Forum http://www.phplinkdirectory.com/forum/
 #
 # @link           http://www.phplinkdirectory.com/
 # @copyright      2004-2013 NetCreated, Inc. (http://www.netcreated.com/)
 # @projectManager David DuVal <david@david-duval.com>
 # @package        PHPLinkDirectory
 # @version        5.1.0 Phoenix Release
 # ################################################################################
 */
error_reporting(E_ALL);
require_once 'init.php';

if (DEBUG) {
    require_once 'include/debug.php';
    //get_execution_time();
}
require_once 'code/App.php';
if (DEBUG) {
    echo get_execution_time();
}

$app = Phpld_App::getInstance();
$app->run();

if (DEBUG) {
    echo get_execution_time();
}
