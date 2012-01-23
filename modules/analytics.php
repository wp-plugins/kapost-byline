<?php
function kapost_byline_has_analytics_code($content)
{
	return strpos($content, '<!-- END KAPOST ANALYTICS CODE -->') !== FALSE;
}

function kapost_byline_get_analytics_code($id)
{
	$kapost_analytics = get_post_meta($id, '_kapost_analytics', true);
	if(empty($kapost_analytics))
		return "";

	extract($kapost_analytics, EXTR_SKIP);

	$code = "
<!-- BEGIN KAPOST ANALYTICS CODE -->
<span id='kapostanalytics_" . esc_attr($post_id) . "'></span>
<script>
<!--
var _kapost_data = _kapost_data || [];
_kapost_data.push([1, '" . esc_js($post_id) . "', '" . esc_js($author_id) . "', '" . esc_js($newsroom_id) . "', escape('" . esc_js($categories) . "')]);
(function(){
var ka = document.createElement('script'); ka.async=true; ka.id='kp_tracker'; ka.src='" . esc_url($url) . "/javascripts/tracker.js';
var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ka, s);
})();
-->
</script>
<!-- END KAPOST ANALYTICS CODE -->
";

	return $code;
}

function kapost_byline_the_content($content)
{
	global $post;

	if(isset($post) && !kapost_byline_has_analytics_code($content))
		return $content . kapost_byline_get_analytics_code($post->ID);

	return $content;
}

add_filter('the_content', 'kapost_byline_the_content');
add_filter('the_content_feed', 'kapost_byline_the_content');
?>
