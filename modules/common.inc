<?php
// Returns the URL to our Kapost Byline Settings Page
function kapost_byline_settings_url()
{
	return admin_url('options-general.php?page='.KAPOST_BYLINE_DEFAULT_SETTINGS_KEY);
}
// Creates a User or Not for native Attributions
function kapost_byline_can_create_user_for_attr()
{
	$settings = kapost_byline_settings();
	return ($settings['attr_create_user'] == 'on');
}
// Get Kapost Byline Settings
function kapost_byline_settings()
{
	global $KAPOST_BYLINE_DEFAULT_SETTINGS;
	return wp_parse_args((array)get_option(KAPOST_BYLINE_DEFAULT_SETTINGS_KEY), $KAPOST_BYLINE_DEFAULT_SETTINGS);
}
// Update Kapost Byline Settings
function kapost_byline_settings_update($settings)
{
	if(!is_array($settings)) $settings = array();
	update_option(KAPOST_BYLINE_DEFAULT_SETTINGS_KEY,$settings);	
}
?>
