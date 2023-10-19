<?php 
/*#################################################################*\
|# Licence Number 029O-0000-02T0-0200
|# -------------------------------------------------------------   #|
|# Copyright (c)2023 PHP Link Directory.                           #|
|# http://www.phplinkdirectory.com                                 #|
\*#################################################################*/
	 
function parse_yturl($url) 
{
    $pattern = '#^(?:https?://)?';    # Optional URL scheme. Either http or https.
    $pattern .= '(?:www\.)?';         #  Optional www subdomain.
    $pattern .= '(?:';                #  Group host alternatives:
    $pattern .=   'youtu\.be/';       #    Either youtu.be,
    $pattern .=   '|youtube\.com';    #    or youtube.com
    $pattern .=   '(?:';              #    Group path alternatives:
    $pattern .=     '/embed/';        #      Either /embed/,
    $pattern .=     '|/v/';           #      or /v/,
    $pattern .=     '|/watch\?v=';    #      or /watch?v=,    
    $pattern .=     '|/watch\?.+&v='; #      or /watch?other_param&v=
    $pattern .=   ')';                #    End path alternatives.
    $pattern .= ')';                  #  End host alternatives.
    $pattern .= '([\w-]{11})';        # 11 characters (Length of Youtube video ids).
    $pattern .= '(?:.+)?$#x';         # Optional other ending URL parameters.
    preg_match($pattern, $url, $matches);
    return (isset($matches[1])) ? $matches[1] : false;
}

//The Youtube's API url
define('YT_API_URL', 'http://gdata.youtube.com/feeds/api/videos?q=');
 
//Change below the video id.
$video_id = parse_yturl($_POST['url']);

//Using cURL php extension to make the request to youtube API
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, YT_API_URL . $video_id);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
//$feed holds a rss feed xml returned by youtube API
$feed = curl_exec($ch);
curl_close($ch);
 
//Using SimpleXML to parse youtube's feed
$xml = simplexml_load_string($feed);
 
$entry = $xml->entry[0];
//If no entry whas found, then youtube didn't find any video with specified id
if(!$entry){
    $result = array('error' => 'No video found for given URL.');
    echo json_encode($result);
    exit;
}

$media = $entry->children('media', true);
$group = $media->group;
 
$title = $group->title;//$title: The video title
$desc = $group->description;//$desc: The video description
$vid_keywords = $group->keywords;//$vid_keywords: The video keywords
$thumb = $group->thumbnail[0];//There are 4 thumbnails, the first one (index 0) is the largest.
//$thumb_url: the url of the thumbnail. $thumb_width: thumbnail width in pixels.
//$thumb_height: thumbnail height in pixels. $thumb_time: thumbnail time in the video
list($thumb_url, $thumb_width, $thumb_height, $thumb_time) = $thumb->attributes();
$content_attributes = $group->content->attributes();
//$vid_duration: the duration of the video in seconds. Ex.: 192.
$vid_duration = $content_attributes['duration'];
//$duration_formatted: the duration of the video formatted in "mm:ss". Ex.:01:54
$duration_formatted = str_pad(floor($vid_duration/60), 2, '0', STR_PAD_LEFT) . ':' . str_pad($vid_duration%60, 2, '0', STR_PAD_LEFT);
 
//echoing the variables for testing purposes:
//echo 'title: ' . $title . '<br />';
//echo 'desc: ' . $desc . '<br />';
//echo 'video keywords: ' . $vid_keywords . '<br />';
//echo 'thumbnail url: ' . $thumb_url . '<br />';
//echo 'thumbnail width: ' . $thumb_width . '<br />';
//echo 'thumbnail height: ' . $thumb_height . '<br />';
//echo 'thumbnail time: ' . $thumb_time . '<br />';
//echo 'video duration: ' . $vid_duration . '<br />';
//echo 'video duration formatted: ' . $duration_formatted;

$vid_duration = json_decode(json_encode($vid_duration), true);
$length = ($vid_duration[0] < 3600) ? gmdate("i:s", $vid_duration[0]) : gmdate("H:i:s", $vid_duration[0]);

$result = array('title' => ucwords(strtolower($title)), 'desc' => ucwords(strtolower($desc)), 'thumb' => $thumb_url, 'length' => $length);

echo json_encode($result);
?>