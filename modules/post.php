<?php
function kapost_byline_custom_fields($raw_custom_fields)
{
	if(!is_array($raw_custom_fields))
		return array();

	$custom_fields = array();
	foreach($raw_custom_fields as $i => $cf)
	{
		$k = sanitize_text_field($cf['key']);
		$v = sanitize_text_field($cf['value']);
		$custom_fields[$k] = $v;
	}

	return $custom_fields;
}

function kapost_is_protected_meta($protected_fields, $field)
{
	if(!in_array($field, $protected_fields))
		return false;

	if(function_exists('is_protected_meta'))
		return is_protected_meta($field, 'post');

	return ($field[0] == '_');
}

function kapost_byline_protected_custom_fields($custom_fields)
{		
	if(!isset($custom_fields['_kapost_protected']))
		return array();

	$protected_fields = array();
	foreach(explode('|', $custom_fields['_kapost_protected']) as $p)
	{
		list($prefix, $keywords) = explode(':', $p);

		$prefix = trim($prefix);
		if(empty($keywords))
		{	
			$protected_fields[] = "_${prefix}";
			continue;
		}

		foreach(explode(',', $keywords) as $k)
		{
			$kk = trim($k);
			$protected_fields[] = "_${prefix}_${kk}";
		}
	}	
		
	$pcf = array();
	foreach($custom_fields as $k => $v)
	{	
		if(kapost_is_protected_meta($protected_fields, $k))
			$pcf[$k] = $v;																								  
	}
	
	return $pcf;
}

function kapost_byline_update_post_data($data, $custom_fields, $blog_id=0)
{
	// if this is a draft then clear the 'publish date' or set our own
	if($data['post_status'] == 'draft')
	{
		if(isset($custom_fields['kapost_publish_date']))
		{
			$post_date = $custom_fields['kapost_publish_date']; // UTC
			$data['post_date'] = get_date_from_gmt($post_date);
			$data['post_date_gmt'] = $post_date;
		}
		else
		{
			$data['post_date'] = '0000-00-00 00:00:00';
			$data['post_date_gmt'] = '0000-00-00 00:00:00';
		}
	}

	// set our custom type
	if(KAPOST_BYLINE_WP3 && isset($custom_fields['kapost_custom_type']))
	{
		$custom_type = $custom_fields['kapost_custom_type'];
		if(!empty($custom_type) && post_type_exists($custom_type))
			$data['post_type'] = $custom_type;
	}

	// exit early in preview mode because we don't want to create the user just yet
	if(isset($GLOBALS['KAPOST_BYLINE_PREVIEW']))
		return $data;

	// create user if necessary
	$uid = kapost_byline_create_user($custom_fields, $blog_id);

	// set our post author
	if($uid !== false && $data['post_author'] != $uid)
		$data['post_author'] = $uid;

	return $data;
}

function kapost_byline_is_simple_field($k)
{
	// keys must be in this format: _simple_fields_fieldGroupID_1_fieldID_1_numInSet_0
	return preg_match('/^_simple_fields_fieldGroupID_[0-9]+_fieldID_[0-9]+_numInSet_[0-9]+$/', $k);
}

function kapost_byline_update_simple_fields($id, $custom_fields)
{
	global $wpdb;

	// remove any existing Simple Fields
	$wpdb->query("DELETE FROM $wpdb->postmeta WHERE post_id = $id AND meta_key LIKE '_simple_fields_fieldGroupID_%'");

	// store Simple Fields specific protected custom fields
	foreach($custom_fields as $k => $v) 
	{	
		// keys must be in this format: _simple_fields_fieldGroupID_1_fieldID_1_numInSet_0
		if(kapost_byline_is_simple_field($k))
		{	
			$value = $custom_fields[$k];

			// is this an image?
			$matches = kapost_byline_validate_image_url($value);
			if(!empty($matches))
			{ 
				$image = $wpdb->get_row($wpdb->prepare("SELECT ID FROM $wpdb->posts WHERE post_type = 'attachment' AND guid = %s LIMIT 1", $value));

				// if the image was found, set the ID
				if(!empty($image) && is_object($image))
					add_post_meta($id, $k, $image->ID);
			}	
			else // default is text field/area
			{	
				add_post_meta($id, $k, $value);
			}	
		}	
	}
}

function kapost_byline_update_post_image_fields($id, $custom_fields)
{
	global $wpdb;

	foreach($custom_fields as $k => $v) 
	{	
		// skip simple fields because those are being handled differently
		if(kapost_byline_is_simple_field($k))
			continue;

		$value = $custom_fields[$k];

		// is this an image?
		$matches = kapost_byline_validate_image_url($value);
		if(empty($matches))
			continue;

		// find the image based on the URL
		$image = $wpdb->get_row($wpdb->prepare("SELECT ID FROM $wpdb->posts WHERE post_type = 'attachment' AND guid = %s LIMIT 1", $value));
		if(empty($image) || !is_object($image))
			continue;

		delete_post_meta($id, $k);
		add_post_meta($id, $k, $image->ID);
	}
}

function kapost_byline_update_hash_custom_fields($id, $custom_fields)
{
	$hash_custom_fields = array();

	foreach($custom_fields as $k => $v) 
	{   
		// starts with?
		if(!empty($v) && strpos($k, '_kapost_hash_') === 0)
		{   
			$kk = str_replace('_kapost_hash_', '', $k);
			$vv = @json_decode(@base64_decode($v), true);

			if(is_array($vv))
				$hash_custom_fields[$kk] = $vv;
		}   
	}   

	foreach($hash_custom_fields as $k => $v) 
	{   
		unset($custom_fields[$k]);

		delete_post_meta($id, $k);
		add_post_meta($id, $k, $v);
	}   
}

function kapost_byline_update_post_meta_data($id, $custom_fields)
{
	// set any "hash" custom fields
	kapost_byline_update_hash_custom_fields($id, $custom_fields);

	// set our featured image
	if(isset($custom_fields['kapost_featured_image']))
	{
		// look up the image by URL which is the GUID (too bad there's NO wp_ specific method to do this, oh well!)
		global $wpdb;
		$thumbnail = $wpdb->get_row($wpdb->prepare("SELECT ID FROM $wpdb->posts WHERE post_type = 'attachment' AND guid = %s LIMIT 1", $custom_fields['kapost_featured_image']));

		// if the image was found, set it as the featured image for the current post
		if(!empty($thumbnail))
		{
			// We support 2.9 and up so let's do this the old fashioned way
			// >= 3.0.1 and up has "set_post_thumbnail" available which does this little piece of mockery for us ...
			update_post_meta($id, '_thumbnail_id', $thumbnail->ID);
		}
	}

	// store our image custom fields as IDs instead of URLs
	$settings = kapost_byline_settings();
	if($settings['image_custom_fields'] == 'on')
		kapost_byline_update_post_image_fields($id, $custom_fields);

	// store our protected custom field required by our analytics
	if(isset($custom_fields['_kapost_analytics_url']))
	{
		delete_post_meta($id, '_kapost_analytics');

		// join them into one for performance and speed
		$kapost_analytics = array();
		foreach($custom_fields as $k => $v)
		{
			// starts with?
			if(strpos($k, '_kapost_analytics_') === 0)
			{
				$kk = str_replace('_kapost_analytics_', '', $k);
				$kapost_analytics[$kk] = $v;
			}
		}

		add_post_meta($id, '_kapost_analytics', $kapost_analytics);
	}

	// store other implicitly 'allowed' protected custom fields
	if(isset($custom_fields['_kapost_protected']))
	{
		foreach(kapost_byline_protected_custom_fields($custom_fields) as $k => $v)
		{
			delete_post_meta($id, $k);
			if(!empty($v)) add_post_meta($id, $k, $v);
		}
	}

	$fields_processed = array();
	foreach($custom_fields as $k => $v)
	{
		if (strpos($k, "_kapost_merged_") === 0) // If the key starts with _kapost_merged_
		{
			$real_key = substr($k, 15); // Grab all the characters after the prefix
			
			delete_post_meta($id, $real_key);
			foreach(explode('|||', $v) as $exploded_field)	// Separate the value by ||| delimiters
			{
				add_post_meta($id, $real_key, $exploded_field); // Add a custom field with the same name for each value
			}

			array_push($fields_processed, $k);
		}
	}
	foreach($fields_processed as $field_processed) 
	{
		delete_post_meta($id, $field_processed); // Clean up by removing the merged key's custom field
		unset($custom_fields[$field_processed]); // Clean up by removing that key from the array
	}

	// check and store protected custom fields used by Simple Fields
	if(defined('EASY_FIELDS_VERSION') || class_exists('simple_fields'))
		kapost_byline_update_simple_fields($id, $custom_fields);

	// match custom fields to custom taxonomies if appropriate
	$taxonomies = array_keys(get_taxonomies(array('_builtin' => false), 'names'));
	if(!empty($taxonomies))
	{
		foreach($custom_fields as $k => $v)
		{																														  
			if(in_array($k, $taxonomies))
				wp_set_object_terms($id, explode(',', $v), $k);
		}
	}
}

function kapost_byline_get_xmlrpc_server()
{
	if(!defined('XMLRPC_REQUEST'))
		return false;

	global $wp_xmlrpc_server;
	if(empty($wp_xmlrpc_server))
		return false;

	$methods = array('metaWeblog.newPost', 'metaWeblog.editPost', 'kapost.newPost', 'kapost.editPost', 'kapost.getPreview');
	if(!in_array($wp_xmlrpc_server->message->methodName, $methods))
		return false;

	return $wp_xmlrpc_server;
}

function kapost_byline_on_insert_post_data($data, $postarr)
{
	$xmlrpc_server = kapost_byline_get_xmlrpc_server();
	if($xmlrpc_server == false)
		return $data;

	$message = $xmlrpc_server->message;
	$args = $message->params; // create a copy
	$xmlrpc_server->escape($args);

	$custom_fields = kapost_byline_custom_fields($args[3]['custom_fields']);
	return kapost_byline_update_post_data($data, $custom_fields, intval($args[0]));
}

function kapost_byline_on_insert_post($id)
{
	$xmlrpc_server = kapost_byline_get_xmlrpc_server();
	if($xmlrpc_server == false)
		return false;

	$message = $xmlrpc_server->message;
	$args = $message->params; // create a copy
	$xmlrpc_server->escape($args);

	$custom_fields = kapost_byline_custom_fields($args[3]['custom_fields']);
	kapost_byline_update_post_meta_data($id, $custom_fields);
}
add_filter('wp_insert_post_data', 'kapost_byline_on_insert_post_data', '999', 2);
add_action('wp_insert_post', 'kapost_byline_on_insert_post');
?>
