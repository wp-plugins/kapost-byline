<?php
function kapost_byline_verify_analytics_url($url)
{
	if(empty($url)) return false;

	$hash= md5($url);
	$hashes = array('7dd0fdf1c9ddf706f2d7db3ac9394f47',
					'6d24b20936cc0813222f0d811bbf3cae',
					'8a0e9264f92e006f5655b4c19d4cab10',
					'2e80e3bdbcfc7099625a183b058d6117',
					'07231c6f8912f13f1fa43436b5854590',
					'58619923a812c8175c245f6180ba9669',
					'84573bbf41383a6a8fc402f9a550c434');

	foreach($hashes as $h)
	{
		if($hash == $h)
			return true;
	}

	return false;
}
function kapost_byline_inject_analytics() 
{
	global $post;

	if(is_home() || is_front_page())
		return;

	if(!isset($post) || ($post->post_status != 'publish') || (strpos($post->post_content, '<!-- END KAPOST ANALYTICS CODE -->') !== FALSE))
		return;

	$kapost_analytics = get_post_meta($post->ID, '_kapost_analytics', true);
	if(empty($kapost_analytics))
		return;

	if(kapost_byline_verify_analytics_url($kapost_analytics['url']) == false)
		return;

	$url = esc_js($kapost_analytics['url']);

	$post_id = esc_js($kapost_analytics['post_id']);

	if(isset($kapost_analytics['site_id']))
		$site_id = esc_js($kapost_analytics['site_id']);
	else
		$site_id = '';

echo "<!-- BEGIN KAPOST ANALYTICS CODE -->
<script type=\"text/javascript\">
<!--
var _kaq = _kaq || [];
_kaq.push([2, '$post_id', '$site_id']);
(function(){
var ka = document.createElement('script'); ka.async=true; ka.id='ka_tracker'; ka.src='$url/ka.js';
var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ka, s);
})();
//--> 
</script>
<!-- END KAPOST ANALYTICS CODE -->";
}

add_action('wp_footer', 'kapost_byline_inject_analytics');
?>
