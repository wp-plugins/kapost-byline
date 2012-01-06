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

function kapost_byline_update_post_data($data, $custom_fields, $blog_id=false)
{
    // if this is a draft then clear the 'publish date'
    if($data['post_status'] == 'draft')
    {
        $data['post_date'] = '0000-00-00 00:00:00';
        $data['post_date_gmt'] = '0000-00-00 00:00:00';
    }

    // set our custom type
    if(KAPOST_BYLINE_WP3 && isset($custom_fields['kapost_custom_type']))
    {
        $custom_type = $custom_fields['kapost_custom_type'];
        if(!empty($custom_type) && post_type_exists($custom_type))
            $data['post_type'] = $custom_type;
    }

    // create user if necessary
    $uid = kapost_byline_create_user($custom_fields, $blog_id);

    // set our post author
    if($uid !== false && $data['post_author'] != $uid)
        $data['post_author'] = $uid;

    return $data;
}

function kapost_byline_update_post_meta_data($id, $custom_fields)
{
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

	if(isset($custom_fields['_kapost_protected']))
	{
		foreach(kapost_byline_protected_custom_fields($custom_fields) as $k => $v)
		{
			delete_post_meta($id, $k);
			if(isset($v) && !empty($v))
				add_post_meta($id, $k, $v);
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

	$methods = array('metaWeblog.newPost', 'metaWeblog.editPost', 'kapost.newPost');
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
	$custom_fields = kapost_byline_custom_fields($message->params[3]['custom_fields']);
	$blog_id = intval($message->params[0]);

	return kapost_byline_update_post_data($data, $custom_fields, $blog_id);
}

function kapost_byline_on_insert_post($id)
{
	$xmlrpc_server = kapost_byline_get_xmlrpc_server();
	if($xmlrpc_server == false)
		return false;

	$message = $xmlrpc_server->message;
	$custom_fields = kapost_byline_custom_fields($message->params[3]['custom_fields']);
	
	kapost_byline_update_post_meta_data($id, $custom_fields);
}
add_filter('wp_insert_post_data', 'kapost_byline_on_insert_post_data', '999', 2);
add_action('wp_insert_post', 'kapost_byline_on_insert_post');
?>
