<?php
function kapost_byline_create_user($custom_fields)
{
	require_once(ABSPATH . WPINC . '/registration.php');

	$uid = email_exists($custom_fields['kapost_author_email']);
	if(!$uid)
	{
		$c = 0;
		$user_name = $user_login = str_replace(" ","",strtolower($custom_fields['kapost_author']));

		// FIXME: find a better way to do this
		// Assuming 1000 collisions is safe enough for now, but there must be
		// a better way to achieve this; the request will time out before
		// reaching 1000 anyway ...
		while(username_exists($user_name))
		{
			$user_name = "$user_login-$c";
			if(++$c == 1000) return false;
		}

		$uid = wp_insert_user(array(
			'user_login'=>esc_sql($user_name),
			'user_pass'=>wp_generate_password(12,false),
			'user_email'=>esc_sql($custom_fields['kapost_author_email']),
			'user_url'=>esc_sql($custom_fields['kapost_author_profile']),
			'display_name'=>esc_sql($custom_fields['kapost_author']),
			'role'=>'contributor'
		));
	}

	return ($uid) ? $uid : false;
}
function kapost_byline_verify_custom_fields($custom_fields)
{
	$required = array("kapost_author",
					  "kapost_author_email",
					  "kapost_author_profile",
					  "kapost_author_avatar",
					  "kapost_post_timestamp");

	foreach($required as $field)
		if(!array_key_exists($field, $custom_fields)) return false;

	return $custom_fields;
}
function kapost_byline_custom_fields($raw_custom_fields)
{
	$custom_fields = array();
	foreach($raw_custom_fields as $i=>$cf)
		$custom_fields[$cf['key']] = $cf['value'];

	return kapost_byline_verify_custom_fields($custom_fields);
}
/*
 * We could make use the existing globals here but it's easier
 * to grab the HTTP_RAW_POST_DATA and parse it into a message
 * ourselves so we have access to all the custom fields even
 * if they weren't inserted in the database yet.
 *
 * Also no need to validate the data again because if we are
 * here it means that the XMLRPC "Server" exposed by WordPress
 * processed this already and therefore the data is all sane
 * and ready to be used.
 */
function kapost_byline_save_post($id)
{
	// GUARD: XMLRPC ONLY!
	if(!defined('XMLRPC_REQUEST') || defined('KAPOST_BYLINE_XMLRPC')) return;

	$message = new IXR_Message(trim(file_get_contents("php://input")));
	if(!$message->parse()) return;

	if($message->methodName != "metaWeblog.newPost") return;

	if( !is_array($message->params[3]) ||
		!is_array($message->params[3]['custom_fields']) ) return;

	$custom_fields = kapost_byline_custom_fields($message->params[3]['custom_fields']);
	if($custom_fields == false) return;

	$post = get_post($id);
	if(!is_object($post)) return;

	$post_needs_update = false;

	if(KAPOST_BYLINE_WP3 && isset($custom_fields['kapost_custom_type']))
	{
		$custom_type = $custom_fields['kapost_custom_type'];
		if(!empty($custom_type) && post_type_exists($custom_type))
		{
			$post->post_type = $custom_type;
			$post_needs_update = true;
		}
	}

	if(kapost_byline_can_create_user_for_attr())
	{
		$uid = kapost_byline_create_user($custom_fields);
		if($uid !== false && $post->post_author != $uid)
		{
			$post->post_author = $uid;
			$post_needs_update = true;
		}
	}

	if($post_needs_update)
	{
		define('KAPOST_BYLINE_XMLRPC', 1);
		wp_update_post((array) $post);
	}
}
add_action('wp_insert_post', 'kapost_byline_save_post');
?>
