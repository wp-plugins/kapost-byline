<?php
/*
	Plugin Name: Kapost Social Publishing Byline
	Plugin URI: http://www.kapost.com/
	Description: Kapost Social Publishing Byline
	Version: 1.0.0
	Author: Kapost
	Author URI: http://www.kapost.com
*/
define('KAPOST_BYLINE_VERSION', '1.0.0');
define('KAPOST_BYLINE_FILENAME',__FILE__);
define('KAPOST_BYLINE_BASENAME',plugin_basename(__FILE__));
define('KAPOST_BYLINE_DIRNAME',str_replace(basename( __FILE__),'',plugin_basename(__FILE__)));
define('KAPOST_BYLINE_DEFAULT_SETTINGS_KEY','kapost_byline_settings');
define('KAPOST_BYLINE_WP3',(get_bloginfo('version') >= 3.0));

$KAPOST_BYLINE_DEFAULT_SETTINGS = array('attr_create_user'=>'on');
$modules = array
(
	'install.php',	
	'common.inc',
	'settings.php',
	'attributions.php'
);

if(KAPOST_BYLINE_WP3) $modules[] = "customtype.php";

foreach($modules as $module) require_once(dirname(__FILE__).'/modules/'.$module);
?>
