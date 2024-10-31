<?php
/*
 Plugin Name: Public Stats
 Plugin URI: http://blog.dubios.ro/Public-stats-wordpress-blog-plugin-unique-visitors-browser-operatng-system-os-ip
 Description: This widget can display the number of unique visitors your blog received since the last 00:00AM. It can also show the Public user's ip, browser and operating system info. Configurable.
 Version: 0.1
 Author: Florin Ercus
 Author URI: http://www.dubios.ro
 */

/*  Copyright 2008  FLORIN ERCUS  (email : flo@dubios.ro)
This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version. This program is distributed in the 
hope that it will be useful, but WITHOUT ANY WARRANTY; without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A 
PARTICULAR PURPOSE.  See the GNU General Public License for more details.

*/


/**
 * returns the browser and its version
 *
 * @return str
 */
function user_browser()
{
	$browsers = "mozilla msie gecko firefox konqueror safari netscape navigator opera mosaic lynx amaya omniweb";
	$browsers = split(" ", $browsers);
	$userAgent = strToLower( $_SERVER['HTTP_USER_AGENT']);
	$ver = ''; $nav = '';
	$l = strlen($userAgent);
	for ($i=0; $i<count($browsers); $i++){
		$browser = $browsers[$i];
		$n = stristr($userAgent, $browser);
		if(strlen($n)>0){
			$ver = "";
			$nav = $browser;
			$j=strpos($userAgent, $nav)+$n+strlen($nav)+1;
			for (; $j<=$l; $j++){
				$s = substr ($userAgent, $j, 1);
				if(is_numeric($ver.$s) )
				$ver .= $s;
				else break;
			}
		}
	}
	$nav = str_replace('msie', 'Internet Explorer', $nav);
	return "$nav $ver";
}

/**
 * Returns users's operating system
 *
 * @return unknown
 */
function user_os()
{
	$OSList = array(
		'Windows 3.11' => 'Win16',
		'Windows 95' => '(Windows 95)|(Win95)|(Windows_95)',
		'Windows 98' => '(Windows 98)|(Win98)',
		'Windows 2000' => '(Windows NT 5.0)|(Windows 2000)',
		'Windows XP' => '(Windows NT 5.1)|(Windows XP)',
		'Windows Server 2003' => '(Windows NT 5.2)',
		'Windows Vista' => '(Windows NT 6.0)',
		'Windows 7' => '(Windows NT 7.0)',
		'Windows NT 4.0' => '(Windows NT 4.0)|(WinNT4.0)|(WinNT)|(Windows NT)',
		'Windows ME' => 'Windows ME',
		'Open BSD' => 'OpenBSD',
		'Sun OS' => 'SunOS',
		'Linux' => '(Linux)|(X11)',
		'Mac OS' => '(Mac_PowerPC)|(Macintosh)',
		'QNX' => 'QNX',
		'BeOS' => 'BeOS',
		'OS/2' => 'OS/2',
		'Search Bot'=>'(nuhk)|(Googlebot)|(Yammybot)|(Openbot)|(Slurp)|(MSNBot)|(Ask Jeeves/Teoma)|(ia_archiver)'
		);
		foreach($OSList as $os=>$val)
		{
			if (eregi($val, $_SERVER['HTTP_USER_AGENT'])) break;
		}
		return $os;
}

/**
 * the widget's body
 *
 * @param unknown_type $args
 */
function widget_PublicStats($args) {
	extract($args);
	$checkFormat = 'd-m-Y';
	if (file_exists($filename = dirname(__FILE__).DIRECTORY_SEPARATOR.'hits.txt'))
	{
		$stat = stat($filename);
	}
	else { echo "<font color=red>[Public Stats]</font> <b>$filename</b> not found"; return; }
	$a = array(
		 'address'=>getenv("REMOTE_ADDR"),
		 'time'=>time(),
		 'user_agent'=>getenv("HTTP_USER_AGENT"),
		 'referer'=>getenv('HTTP_REFERER'),
		 'url'=>"http://".$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']
	);
	file_put_contents(
		$filename, 
		serialize($a)."^@^", 
		date($checkFormat, $stat['mtime']) != date($checkFormat) ? null : FILE_APPEND //reset if needed
	);
	$hits = explode('^@^', trim(file_get_contents($filename)));
	foreach ($hits as $i=>$hit) if (!$hit) unset($hits[$i]); else $hits[$i] = unserialize($hit);
	$addresses = array();
	foreach ($hits as $hit) array_push($addresses, $hit['address']);
	$addresses = array_unique($addresses);
	$visitors = count($addresses);

	$options = get_option("widget_PublicStats");
	if (!is_array( $options ))
	{
		$options = array(
			'title' => 'Public Stats',
			'text' => "<big>%unique%</big> visitors today.<BR>Your IP is %ip%"
			);
	}

	echo $before_widget;
	echo $before_title;
	echo $options['title'];
	echo $after_title;
	//Our Widget Content
	echo str_replace(
	array('%unique%', '%ip%', '%browser%', '%os%'),
	array($visitors, $a['address'], user_browser(), user_os()),
	$options['text']
	);
	echo $after_widget;
}

/**
 * Widget's admin panel
 *
 */
function PublicStats_control()
{
	$options = get_option("widget_PublicStats");
	if (!is_array( $options ))
	{
		$options = array(
    		'title' => 'Public Stats',
    		'text' => '%unique% visitors today'
    	);
	}
	if ($_POST['PublicStats-Submit'])
	{
		$options['title'] = $_POST['PublicStats-WidgetTitle'];
		$options['text'] = $_POST['PublicStats-WidgetText'];

		update_option("widget_PublicStats", $options);
	}
	?>
<p><label for="PublicStats-WidgetTitle">Widget title: </label><BR>
<input style="width: 200px;" type="text" id="PublicStats-WidgetTitle"
	name="PublicStats-WidgetTitle" value="<?php echo $options['title'];?>" />
<label for="PublicStats-WidgetText"><BR>
Template: </label><BR>
<textarea style="width: 200px;" id="PublicStats-WidgetText"
	name="PublicStats-WidgetText"><?php echo $options['text'];?>
	<strong>%unique%</strong> visitors today.<BR>Your IP is <strong>%ip%</strong>.
	</textarea>
<BR>
<BR>
You should use <small>
<ul>
	<li><span style="font-size: 12px; color: red;"><b>%unique%</b></span>
	as number of <u>unique</u> visitors since <u>00:00AM</u></li>
	<li><span style="font-size: 12px; color: red;"><b>%ip%</b></span> as
	the current visitor's IP</li>
	<li><span style="font-size: 12px; color: red;"><b>%browser%</b></span>
	as the current browser</li>
	<li><span style="font-size: 12px; color: red;"><b>%os%</b></span> as
	the current operatng system</li>
</ul>
</small> <input type="hidden" id="PublicStats-Submit"
	name="PublicStats-Submit" value="1" /></p>
	<?php
}

/**
 * function to run on every refresh, after the plugins are loaded
 *
 */
function PublicStats_init()
{
	register_sidebar_widget('Public Stats', 'widget_PublicStats');
	register_widget_control('Public Stats', 'PublicStats_control');
}

add_action("plugins_loaded", "PublicStats_init");
?>