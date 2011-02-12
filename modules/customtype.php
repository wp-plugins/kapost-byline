<?php
function kapost_byline_xmlrpc_custom_type($data, $post) 
{
	if(defined('KAPOST_BYLINE_CUSTOM_TYPE') && (defined('XMLRPC_REQUEST') || defined('APP_REQUEST'))) 
		$data['post_type'] = KAPOST_BYLINE_CUSTOM_TYPE;

	return $data;
}
add_filter('wp_insert_post_data', 'kapost_byline_xmlrpc_custom_type', 99, 2);
?>
