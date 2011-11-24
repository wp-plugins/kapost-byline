<?php
function kapost_byline_xmlrpc_version()
{
	return KAPOST_BYLINE_VERSION;
}
function kapost_byline_xmlrpc_newPost($args)
{
	global $wp_xmlrpc_server;
	    
	$wp_xmlrpc_server->escape($args);
	
	$username	= $args[1];
	$password	= $args[2];
	$data		= $args[3];

	if(!$wp_xmlrpc_server->login_pass_ok($username, $password))
		return $wp_xmlrpc_server->error;

	$custom_fields = kapost_byline_custom_fields($data['custom_fields']);
	$uid = false;
	
	$id = $wp_xmlrpc_server->mw_newPost($args);

	if(is_string($id))
		kapost_byline_update_post($id, $custom_fields, $uid);

	return $id;
}
function kapost_byline_xmlrpc($methods)
{
	$methods['kapost.version'] = 'kapost_byline_xmlrpc_version';
	$methods['kapost.newPost'] = 'kapost_byline_xmlrpc_newPost';
	return $methods;
}
add_filter('xmlrpc_methods', 'kapost_byline_xmlrpc');
?>
