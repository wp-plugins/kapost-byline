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
<span id='kapostanalytics' pid='" . esc_attr($post_id) . "' aid='" . esc_attr($author_id) . "' nid='" . esc_attr($newsroom_id) . "' cats='" . esc_attr($categories) . "' url='" . $url . "'></span>
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

function kapost_inject_footer_script() {
  echo '<script><!--
var _kapost_data = _kapost_data || [];
m = document.getElementById("kapostanalytics");
_kapost_data.push([1, m.getAttribute("pid"), m.getAttribute("aid"), m.getAttribute("nid"), m.getAttribute("cats")]);
(function() {
var ka = document.createElement(\'script\'); 
ka.async=true; 
ka.id="kp_tracker"; 
ka.src=m.getAttribute("url") + "/javascripts/tracker.js";
var s = document.getElementsByTagName(\'script\')[0]; 
s.parentNode.insertBefore(ka, s);
})();
//--></script>';
}

add_filter('the_content', 'kapost_byline_the_content');
add_filter('the_content_feed', 'kapost_byline_the_content');
add_action('wp_footer', 'kapost_inject_footer_script');
?>
