<?php
function kapost_byline_create_user($custom_fields, $blog_id=false)
{
	if(	empty($custom_fields['kapost_author_email']) ||
		empty($custom_fields['kapost_author_profile']) ||
		empty($custom_fields['kapost_author']) )
		return false;

	$author = $custom_fields['kapost_author'];
	$profile= $custom_fields['kapost_author_profile'];
	$email	= $custom_fields['kapost_author_email'];

	$settings = kapost_byline_settings();
	if($settings['attr_create_user'] != 'on')
		return false;

	require_once(ABSPATH . WPINC . '/registration.php');
	$uid = email_exists($email);
	if(!$uid)
	{
		$c = 0;
		$user_name = $user_login = str_replace(" ","",strtolower($author));

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
			'user_email'=>esc_sql($email),
			'user_url'=>esc_sql($profile),
			'display_name'=>esc_sql($author),
			'role'=>'contributor'
		));
	}
	else if($blog_id && function_exists('is_user_member_of_blog') && !is_user_member_of_blog($uid, $blog_id))
	{
		$uid = false;
	}

	return ($uid) ? $uid : false;
}
?>
