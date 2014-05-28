<?php
function kapost_byline_wpml_do_action($action, $str_to_int)
{
	if(!class_exists('SitePress'))
		return;

	if($str_to_int)
	{
		$pattern = '/<value><string>(.*?)<\/string><\/value>/';
		$replacement = '<value><int>$1</int></value>';

		global $HTTP_RAW_POST_DATA;
		$HTTP_RAW_POST_DATA = preg_replace($pattern, $replacement, $HTTP_RAW_POST_DATA, 1);
	}

	if($action != null)
		do_action('xmlrpc_call', $action);
}
?>
