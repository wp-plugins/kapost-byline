<?php
function kapost_byline_xmlrpc_version()
{
	return KAPOST_BYLINE_VERSION;
}

function kapost_byline_xmlrpc_newPost($args)
{
	global $wp_xmlrpc_server;
	    
	$wp_xmlrpc_server->escape($args);

	$blog_id	= intval($args[0]);
	$username	= $args[1];
	$password	= $args[2];
	$data		= $args[3];

	if(!$wp_xmlrpc_server->login($username, $password))
		return $wp_xmlrpc_server->error;
	
	return $wp_xmlrpc_server->mw_newPost($args);
}

function kapost_byline_xmlrpc($methods)
{
	$methods['kapost.version'] = 'kapost_byline_xmlrpc_version';
	$methods['kapost.newPost'] = 'kapost_byline_xmlrpc_newPost';
	return $methods;
}
add_filter('xmlrpc_methods', 'kapost_byline_xmlrpc');
?>
