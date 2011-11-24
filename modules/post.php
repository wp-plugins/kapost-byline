<?php
function kapost_byline_custom_fields($raw_custom_fields)
{
	if(!is_array($raw_custom_fields))
		return array();

	$custom_fields = array();
	foreach($raw_custom_fields as $i => $cf)
		$custom_fields[$cf['key']] = $cf['value'];

	return $custom_fields;
}
function kapost_byline_update_post($id, $custom_fields, $uid=false)
{
	$post = get_post($id);
	if(!is_object($post)) return false;

	$post_needs_update = false;

	// if this is a draft then clear the 'publish date'
	if($post->post_status == 'draft')
	{
		$post->post_date = '0000-00-00 00:00:00';
		$post->post_date_gmt = '0000-00-00 00:00:00';
		$post_needs_update = true;
	}

	// set our custom type
	if(KAPOST_BYLINE_WP3 && isset($custom_fields['kapost_custom_type']))
	{
		$custom_type = $custom_fields['kapost_custom_type'];
		if(!empty($custom_type) && post_type_exists($custom_type))
		{
			$post->post_type = $custom_type;
			$post_needs_update = true;
		}
	}

	// set our featured image
	if(isset($custom_fields['kapost_featured_image']))
	{
		// look up the image by URL which is the GUID (too bad there's NO wp_ specific method to do this, oh well!)
		global $wpdb;
		$thumbnail = $wpdb->get_row($wpdb->prepare("SELECT ID FROM $wpdb->posts WHERE post_type = 'attachment' AND guid = %s", $custom_fields['kapost_featured_image']));

		// if the image was found, set it as the featured image for the current post
		if(!empty($thumbnail))
		{
			// We support 2.9 and up so let's do this the old fashioned way
			// >= 3.0.1 and up has "set_post_thumbnail" available which does this little piece of mockery for us ...
			update_post_meta($id, '_thumbnail_id', $thumbnail->ID);
		}
	}
	
	// create user if necessary
	$uid = kapost_byline_create_user($custom_fields);
	// set our post author
	if($uid !== false && $post->post_author != $uid)
	{
		$post->post_author = $uid;
		$post_needs_update = true;
	}

	// if any changes has been made above update the post once
	if($post_needs_update)
		wp_update_post((array) $post);

	return true;
}
// backwards compatibility
function kapost_byline_on_insert_post($id)
{
	if(!defined('XMLRPC_REQUEST') || defined('KAPOST_BYLINE_XMLRPC')) 
		return;

	global $wp_xmlrpc_server;

	$message = $wp_xmlrpc_server->message;
	if($message->methodName == 'metaWeblog.newPost' && is_array($message->params[3]))
	{
		define('KAPOST_BYLINE_XMLRPC', true);

		$custom_fields = kapost_byline_custom_fields($message->params[3]['custom_fields']);
		kapost_byline_update_post($id, $custom_fields);
	}
}
add_action('wp_insert_post', 'kapost_byline_on_insert_post');
?>
