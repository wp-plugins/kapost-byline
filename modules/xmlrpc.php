<?php
function kapost_byline_xmlrpc_version()
{
	return KAPOST_BYLINE_VERSION;
}

function kapost_byline_xmlrpc_die($message)
{
	$error = new IXR_Error(500, print_r($message, true));
	die($error->getXml());
}

function kapost_byline_xmlrpc_newPost($args)
{
	global $wp_xmlrpc_server;

	$post_id = $wp_xmlrpc_server->mw_newPost($args);

	if(is_string($post_id))
	{
		kapost_byline_wpml_new_post($post_id, $args);
		kapost_byline_wpml_update_terms($post_id, $args);
	}

	return $post_id;
}

function kapost_byline_xmlrpc_editPost($args)
{
	global $wp_xmlrpc_server, $current_site;
	
	if(KAPOST_BYLINE_WP3DOT4 == false)
	{
		kapost_byline_wpml_do_action(null, true);
		
		$result = $wp_xmlrpc_server->mw_editPost($args);

		if($result === true)
			kapost_byline_wpml_update_terms($args[0], $args);

		return $result;
	}

	$_args = $args;
	$wp_xmlrpc_server->escape($_args);

	$blog_id	= $current_site->id;
	$post_id	= intval($_args[0]);
	$username	= $_args[1];
	$password	= $_args[2];
	$data		= $_args[3];
	$publish	= $_args[4];

	if(!$user = $wp_xmlrpc_server->login($username, $password))
		return $wp_xmlrpc_server->error;
	
	if(!current_user_can('edit_post', $post_id))
		return new IXR_Error(401, __('Sorry, you cannot edit this post.'));

	$post = get_post($post_id);
	if(!is_object($post) || !isset($post->ID))
		return new IXR_Error(404, __('Invalid post ID.'));

	if(in_array($post->post_type, array('post', 'page')))
	{
		kapost_byline_wpml_do_action(null, true);

		$result = $wp_xmlrpc_server->mw_editPost($args);

		if($result === true)
			kapost_byline_wpml_update_terms($post_id, $args);

		return $result;
	}

	// to avoid double escaping the content structure in wp_editPost
	// point data to the original structure
	$data = $args[3];

	$content_struct = array();
	$content_struct['post_type'] = $post->post_type; 
	$content_struct['post_status'] = $publish ? 'publish' : 'draft';

	if(isset($data['title']))
		$content_struct['post_title'] = $data['title'];

	if(isset($data['description']))
		$content_struct['post_content'] = $data['description'];

	if(isset($data['custom_fields']))
		$content_struct['custom_fields'] = $data['custom_fields'];

	if(isset($data['mt_excerpt']))
		$content_struct['post_excerpt'] = $data['mt_excerpt'];

	if(isset($data['mt_keywords']) && !empty($data['mt_keywords']))
		$content_struct['terms_names']['post_tag'] = explode(',', $data['mt_keywords']);

	if(isset($data['categories']) && !empty($data['categories']) && is_array($data['categories']))
		$content_struct['terms_names']['category'] = $data['categories'];

	kapost_byline_wpml_do_action('metaWeblog.editPost', true);
	$result = $wp_xmlrpc_server->wp_editPost(array($blog_id, $args[1], $args[2], $args[0], $content_struct));

	if($result === true)
		kapost_byline_wpml_update_terms($post_id, $args);

	return $result;
}

function kapost_byline_xmlrpc_getPost($args)
{
	global $wp_xmlrpc_server;
	kapost_byline_wpml_do_action(null, true);
	return $wp_xmlrpc_server->mw_getPost($args);
}

function kapost_byline_xmlrpc_newMediaObject($args)
{
	global $wpdb, $wp_xmlrpc_server;

	$_args = $args;

	$blog_id	= intval($_args[0]);
	$username	= $wpdb->escape($_args[1]);
	$password	= $wpdb->escape($_args[2]);
	$data		= $_args[3];

	if(!$user = $wp_xmlrpc_server->login($username, $password))
		return $wp_xmlrpc_server->error;

	if(!current_user_can('upload_files'))
		return new IXR_Error(401, __('You are not allowed to upload files to this site.'));

	$image = $wp_xmlrpc_server->mw_newMediaObject($args);
	if(!is_array($image) || empty($image['url']))
		return $image;

	$attachment = $wpdb->get_row($wpdb->prepare("SELECT ID FROM $wpdb->posts WHERE post_type = 'attachment' AND guid = %s LIMIT 1", $image['url']));
	if(empty($attachment))
		return $image;

	$update_attachment = false;

	if(isset($data['description']))
	{
		$attachment->post_content = sanitize_text_field($data['description']);
		$update_attachment = true;
	}

	if(isset($data['title']))
	{
		$attachment->post_title	= sanitize_text_field($data['title']);
		$update_attachment = true;
	}

	if(isset($data['caption']))
	{
		$attachment->post_excerpt = sanitize_text_field($data['caption']);
		$update_attachment = true;
	}

	if($update_attachment) 
		wp_update_post($attachment);

	if(isset($data['alt'])) 
		add_post_meta($attachment->ID, '_wp_attachment_image_alt', sanitize_text_field($data['alt']));

	$image['id'] = $attachment->ID;
	return $image;
}

function kapost_byline_xmlrpc_getPermalink($args)
{
	global $wp_xmlrpc_server;
	$wp_xmlrpc_server->escape($args);

	$post_id	= intval($args[0]);
	$username	= $args[1];
	$password	= $args[2];

	if(!$user = $wp_xmlrpc_server->login($username, $password))
		return $wp_xmlrpc_server->error;
	
	if(!current_user_can('edit_post', $post_id))
		return new IXR_Error(401, __('Sorry, you cannot edit this post.'));

	$post = get_post($post_id);
	if(!is_object($post) || !isset($post->ID))
		return new IXR_Error(401, __('Sorry, you cannot edit this post.'));

	// play nice with the utterly broken No Category Parents plugin :) (sigh)
	if(function_exists('myfilter_category') || function_exists('my_insert_rewrite_rules'))
	{
		remove_filter('pre_post_link'       ,'filter_category');
		remove_filter('user_trailingslashit','myfilter_category');
		remove_filter('category_link'       ,'filter_category_link');
		remove_filter('rewrite_rules_array' ,'my_insert_rewrite_rules');
		remove_filter('query_vars'          ,'my_insert_query_vars');
	}

	list($permalink, $post_name) = get_sample_permalink($post->ID);
	$permalink = str_replace(array('%postname%', '%pagename%'), $post_name, $permalink);

	if(strpos($permalink, "%") === false) # make sure it doesn't contain %day%, etc.
			return $permalink;

	return get_permalink($post);
}

function kapost_byline_xmlrpc_wck_is_installed()
{
    return defined('WCK_PLUGIN_DIR');
}

function kapost_byline_xmlrpc($methods)
{
	$methods['kapost.version']			= 'kapost_byline_xmlrpc_version';
	$methods['kapost.newPost']			= 'kapost_byline_xmlrpc_newPost';
	$methods['kapost.editPost']			= 'kapost_byline_xmlrpc_editPost';
	$methods['kapost.getPost']			= 'kapost_byline_xmlrpc_getPost';
	$methods['kapost.newMediaObject']	= 'kapost_byline_xmlrpc_newMediaObject';
	$methods['kapost.getPermalink']		= 'kapost_byline_xmlrpc_getPermalink';
	$methods['kapost.wckIsInstalled'] = 'kapost_byline_xmlrpc_wck_is_installed';
	return $methods;
}
add_filter('xmlrpc_methods', 'kapost_byline_xmlrpc');
?>
