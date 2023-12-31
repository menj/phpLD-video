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
	 	
require_once 'init.php';

	/*
	
		xmoov-php 0.9
		Development version 0.9.3 beta
		
		by: Eric Lorenzo Benjamin jr. webmaster (AT) xmoov (DOT) com
		originally inspired by Stefan Richter at flashcomguru.com
		bandwidth limiting by Terry streamingflvcom (AT) dedicatedmanagers (DOT) com
		Flowplayer compatibility by Rich Bellamy rich (@+) rmbwebs d0t com
		
		This work is licensed under the Creative Commons Attribution-NonCommercial-ShareAlike 3.0 License.
		For more information, visit http://creativecommons.org/licenses/by-nc-sa/3.0/
		For the full license, visit http://creativecommons.org/licenses/by-nc-sa/3.0/legalcode 
		or send a letter to Creative Commons, 543 Howard Street, 5th Floor, San Francisco, California, 94105, USA.
		
		
	*/



	
	//	SCRIPT CONFIGURATION
	
	//------------------------------------------------------------------------------------------
	//	MEDIA PATH
	//
	//	you can configure these settings to point to video files outside the public html folder.
	//------------------------------------------------------------------------------------------
	
	// points to server root
	define('XMOOV_PATH_ROOT', SITE_URL);

	// points to the folder containing the video files. Should start and end with '/'
	define('XMOOV_PATH_FILES', 'uploads/');
	
	
	
	//------------------------------------------------------------------------------------------
	//	SCRIPT BEHAVIOR
	//------------------------------------------------------------------------------------------
	
	//set to TRUE to use bandwidth limiting.
	define('XMOOV_CONF_LIMIT_BANDWIDTH', TRUE);
	
	//set to FALSE to prohibit caching of video files.
	define('XMOOV_CONF_ALLOW_FILE_CACHE', FALSE);
	
	
	
	//------------------------------------------------------------------------------------------
	//	BANDWIDTH SETTINGS
	//
	//	these settings are only needed when using bandwidth limiting.
	//	
	//	bandwidth is limited my sending a limited amount of video data(XMOOV_BW_PACKET_SIZE),
	//	in specified time intervals(XMOOV_BW_PACKET_INTERVAL). 
	//	avoid time intervals over 1.5 seconds for best results.
	//	
	//	you can also control bandwidth limiting via http command using your video player.
	//	the function getBandwidthLimit($part) holds three preconfigured presets(low, mid, high),
	//	which can be changed to meet your needs
	//------------------------------------------------------------------------------------------	
	
	//set how many kilobytes will be sent per time interval
	define('XMOOV_BW_PACKET_SIZE', 90);
	
	//set the time interval in which data packets will be sent in seconds.
	define('XMOOV_BW_PACKET_INTERVAL', 0.3);
	
	//set to TRUE to control bandwidth externally via http.
	define('XMOOV_CONF_ALLOW_DYNAMIC_BANDWIDTH', TRUE);
	
	define('XMOOV_GET_BANDWIDTH', 'bw');
	
	//------------------------------------------------------------------------------------------
	//	DYNAMIC BANDWIDTH CONTROL
	//------------------------------------------------------------------------------------------
	
	function getBandwidthLimit($part)
	{
		switch($part)
		{
			case 'interval' :
				switch($_GET[XMOOV_GET_BANDWIDTH])
				{
					case 'low' :
						return 1;
					break;
					case 'mid' :
						return 0.5;
					break;
					case 'high' :
						return 0.3;
					break;
					default :
						return XMOOV_BW_PACKET_INTERVAL;
					break;
				}
			break;
			case 'size' :
				switch($_GET[XMOOV_GET_BANDWIDTH])
				{
					case 'low' :
						return 10;
					break;
					case 'mid' :
						return 40;
					break;
					case 'high' :
						return 90;
					break;
					default :
						return XMOOV_BW_PACKET_SIZE;
					break;
				}
			break;
		}
	}
	
	
	
	//------------------------------------------------------------------------------------------
	//	INCOMING GET VARIABLES CONFIGURATION
	//	
	//	use these settings to configure how video files, seek position and bandwidth settings are accessed by your player
	// 
	// NOTE TO Flowplayer users:  XMOOV_GET_POSITION must be set to 'start' to
	//                            work with Flowplayer, and the other two don't matter
	//------------------------------------------------------------------------------------------
	
	define('XMOOV_GET_FILE', 'file');
	define('XMOOV_GET_POSITION', 'start');
	define('XMOOV_GET_AUTHENTICATION', 'key');
	
	
	
	//	END SCRIPT CONFIGURATION - do not change anything beyond this point if you do not know what you are doing
	
	
	
	//------------------------------------------------------------------------------------------
	//	PROCESS FILE REQUEST
	//------------------------------------------------------------------------------------------
	
	if (isset($_SERVER['PATH_INFO']) || isset($_GET[XMOOV_GET_FILE]))
	{
		//	PROCESS VARIABLES
		
		# get seek position
		if (isset($_GET[XMOOV_GET_POSITION])) {
			$seekPos = intval($_GET[XMOOV_GET_POSITION]);
		} else {
			$seekPos = 0;
		}
		
		# get file name
		if (isset($_SERVER['PATH_INFO'])) {
			$fileName = htmlspecialchars(ltrim($_SERVER['PATH_INFO'], "/"));
		} else {
			$fileName = htmlspecialchars($_GET[XMOOV_GET_FILE]);
		}
		
		# assemble file path
		$file = XMOOV_PATH_ROOT . XMOOV_PATH_FILES . $fileName;
		
		# assemble packet interval
		$packet_interval = (XMOOV_CONF_ALLOW_DYNAMIC_BANDWIDTH && isset($_GET[XMOOV_GET_BANDWIDTH])) ? getBandwidthLimit('interval') : XMOOV_BW_PACKET_INTERVAL;
		# assemble packet size
		$packet_size = ((XMOOV_CONF_ALLOW_DYNAMIC_BANDWIDTH && isset($_GET[XMOOV_GET_BANDWIDTH])) ? getBandwidthLimit('size') : XMOOV_BW_PACKET_SIZE) * 1042;
		
		# security improved by by TRUI www.trui.net
		if (!file_exists($file))
		{
			print('<b>ERROR:</b> xmoov-php could not find (' . $file . ') please check your settings.'); 
			exit();
		}
		if(file_exists($file) && strrchr($fileName, '.') == '.flv' && strlen($fileName) > 2 && !eregi(basename($_SERVER['SCRIPT_NAME']), $fileName) && ereg('^[^./][^/]*$', $fileName))
		{
			$fh = fopen($file, 'rb') or die ('<b>ERROR:</b> xmoov-php could not open (' . $fileName . ')');
				
			$fileSize = filesize($file) - (($seekPos > 0) ? $seekPos  + 1 : 0);
			
			//	SEND HEADERS
			if(!XMOOV_CONF_ALLOW_FILE_CACHE)
			{
				# prohibit caching (different methods for different clients)
				session_cache_limiter("nocache");
				header("Expires: Thu, 19 Nov 1981 08:52:00 GMT");
				header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
				header("Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0");
				header("Pragma: no-cache");
			}
			
			# content headers
			header("Content-Type: video/x-flv");
			header("Content-Disposition: attachment; filename=\"" . $fileName . "\"");
			
			# FLV file format header
			if($seekPos != 0) 
			{
				header("Content-Length: " . ($fileSize + 13));
				print('FLV');
				print(pack('C', 1));
				print(pack('C', 1));
				print(pack('N', 9));
				print(pack('N', 9));
			}
			else
			{
				header("Content-Length: " . $fileSize);
			}
			
			# seek to requested file position
			fseek($fh, $seekPos);
			
			# output file
			while(!feof($fh)) 
			{
				# use bandwidth limiting - by Terry
				if(XMOOV_CONF_LIMIT_BANDWIDTH)
				{
					# get start time
					list($usec, $sec) = explode(' ', microtime());
					$time_start = ((float)$usec + (float)$sec);
					# output packet
					print(fread($fh, $packet_size));
					# get end time
					list($usec, $sec) = explode(' ', microtime());
					$time_stop = ((float)$usec + (float)$sec);
					# wait if output is slower than $packet_interval
					$time_difference = $time_stop - $time_start;
					if($time_difference < (float)$packet_interval)
					{
						usleep((float)$packet_interval * 1000000 - (float)$time_difference * 1000000);
					}
				}
				else
				{
					# output file without bandwidth limiting
					print(fread($fh, filesize($file))); 
				}
			}
			
		}
		
	}
?>