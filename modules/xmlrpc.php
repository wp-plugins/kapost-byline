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

function kapost_byline_xmlrpc_editPost($args)
{
	global $wp_xmlrpc_server, $current_site;
	
	if(KAPOST_BYLINE_WP3DOT4 == false)
		return $wp_xmlrpc_server->mw_editPost($args);

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
		return $wp_xmlrpc_server->mw_editPost($args);

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

	return $wp_xmlrpc_server->wp_editPost(array($blog_id, $args[1], $args[2], $args[0], $content_struct));
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

	if(!empty($post->post_title) && in_array($post->post_status, array('draft', 'pending', 'auto-draft')))
	{
		$sample_post = clone $post;
		$sample_post->filter = 'sample';
		$sample_post->post_status = 'publish';

		if(empty($sample_post->post_date) || $sample_post->post_date == '0000-00-00 00:00:00')
		{
			$sample_post->post_date = current_time('mysql');
			$sample_post->post_date_gmt = current_time('mysql', 1);
		}

		if(empty($sample_post->post_name))
		{
			$sample_post->post_name = wp_unique_post_slug(sanitize_title($sample_post->post_title), 
														  $sample_post->ID, 
														  $sample_post->post_status, 
														  $sample_post->post_type, 
														  $sample_post->post_parent);
		}

		$sample_permalink = get_permalink($sample_post);
		if(strpos($sample_permalink, "%") === false) # make sure it doesn't contain %day%, etc.
			return $sample_permalink;
	}

	return get_permalink($post);
}

function kapost_byline_xmlrpc($methods)
{
	$methods['kapost.version'] = 'kapost_byline_xmlrpc_version';
	$methods['kapost.newPost'] = 'kapost_byline_xmlrpc_newPost';
	$methods['kapost.editPost'] = 'kapost_byline_xmlrpc_editPost';
	$methods['kapost.newMediaObject'] = 'kapost_byline_xmlrpc_newMediaObject';
	$methods['kapost.getPermalink']	= 'kapost_byline_xmlrpc_getPermalink';
	return $methods;
}
add_filter('xmlrpc_methods', 'kapost_byline_xmlrpc');
?>
