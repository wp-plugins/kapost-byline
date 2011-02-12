<?php
function kapost_byline_is_plugin_active($path) 
{
	return in_array($path,apply_filters('active_plugins',get_option('active_plugins')));
}
if(kapost_byline_is_plugin_active('kapost-community-publishing/kapost.php') && !isset($_POST['submit']))
{
	function kapost_byline_warning() 
	{
		echo "<div id='kapost-byline-warning' class='updated fade'>
			  <p><strong>You must disable Kapost Social Publishing if you want to use Kapost Social Publishing Byline</strong>.</p>
			  </div>";
	}
	add_action('admin_notices', 'kapost_byline_warning');
}
function kapost_byline_activate()
{
	// Enable XMLRPC
	update_option('enable_xmlrpc', 1);
}
register_activation_hook(KAPOST_BYLINE_FILENAME,'kapost_byline_activate');
?>
