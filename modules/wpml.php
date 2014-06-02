<?php
function kapost_byline_wpml_do_action($action, $str_to_int)
{
	if(!class_exists('SitePress'))
		return;

	if($action == 'metaWeblog.newPost' && is_array($str_to_int))
	{
		global $sitepress;

		if(!isset($sitepress))
			return;

		$post_id = $str_to_int[0];
		$args	 = $str_to_int[1];

		if(empty($args) || !is_array($args[3]))
			return;

		if(!isset($args[3]['custom_fields']) || !is_array($args[3]['custom_fields']))
			return;

		$custom_fields = $args[3]['custom_fields'];

		$language_code = null;
		foreach($custom_fields as $cf)
		{
			switch($cf['key'])
			{
				// if we got trid then this is a translation of something
				// and we should ignore anything below and let the 
				// default behaviour
				case '_wpml_trid':
					return;

				case '_wpml_language':
					$language_code = $cf['value'];
					break;
			}
		}

		// if we do not have a language code or if the language code is the default
		// language then we let the default behaviour
		if($language_code == null || $language_code == $sitepress->get_default_language())
			return;

		if(!array_key_exists($language_code, $sitepress->get_active_languages()))
			return;

		$post_type = 'post_' . get_post_type($post_id);
		$sitepress->set_element_language_details($post_id, $post_type, false, $language_code);

		return;
	}

	if($str_to_int)
	{
		$pattern = '/<value><string>(.*?)<\/string><\/value>/';
		$replacement = '<value><int>$1</int></value>';

		global $HTTP_RAW_POST_DATA;
		$HTTP_RAW_POST_DATA = preg_replace($pattern, $replacement, $HTTP_RAW_POST_DATA, 1);
	}

	if($action != null)
		do_action('xmlrpc_call', $action);
}
?>
