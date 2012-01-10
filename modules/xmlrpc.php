<?php
function kapost_byline_xmlrpc_version()
{
	return KAPOST_BYLINE_VERSION;
}

function kapost_byline_xmlrpc_newPost($args)
{
	global $wp_xmlrpc_server;
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
