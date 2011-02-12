<?php
add_action('admin_init', 'kapost_byline_admin_init');
function kapost_byline_admin_init()
{
	$base_url =  WP_PLUGIN_URL."/".KAPOST_BYLINE_DIRNAME;

	wp_register_style(KAPOST_BYLINE_DEFAULT_SETTINGS_KEY,$base_url.'/modules/settings.css');
	wp_enqueue_style(KAPOST_BYLINE_DEFAULT_SETTINGS_KEY);
}
add_action('admin_menu', 'kapost_byline_settings_menu');
function kapost_byline_settings_menu() 
{
	if(function_exists("add_submenu_page"))
	    add_submenu_page('options-general.php','Kapost Byline', 'Kapost Byline', 'manage_options', 'kapost_byline_settings', 'kapost_byline_settings_options');
}
add_filter('plugin_action_links', 'kapost_byline_page_settings_link', 10, 2);
function kapost_byline_page_settings_link($links, $file) 
{
	if($file == KAPOST_BYLINE_BASENAME) 
	{
		$link = '<a href="'.kapost_byline_settings_url().'">Settings</a>';
		array_unshift($links,$link); 
	}

	return $links;
}
function kapost_byline_settings_form($instance)
{
	$attr_checked = ($instance['attr_create_user'] == 'on') ?' checked="checked"':'';
	$attr_options = '<h3>Attribution Options</h3>
						<blockquote>
							<input type="checkbox" name="'.KAPOST_BYLINE_DEFAULT_SETTINGS_KEY.'[attr_create_user]"'.$attr_checked.'/> Create a new WordPress user for each promoted user unless their account (based on email) already exists. 
							</blockquote>';

	echo '
		<form action="" method="post" autocomplete="off" id="options_form">
		'.$attr_options.'
			<p class="submit">
				<input type="submit" value="Update Settings" id="submit" class="button-primary" name="submit"/>
			</p>
		</form>
	</div>';
}
function kapost_byline_message($msg)
{
	if(empty($msg))	return;
	echo "<div class=\"updated fade\" id=\"message\" style=\"background-color:#fffbcc;\"><p><strong>{$msg}</strong></p></div>";
}
function kapost_byline_settings_form_update($new_instance, $old_instance)
{
	if(!is_array($new_instance)) $new_instance = array();

	// FIXME: use default array?!
	$instance = array('attr_create_user'=>'','custom_type'=>'');
	if($new_instance['attr_create_user'] == 'on')
		$instance['attr_create_user'] = 'on';

	kapost_byline_settings_update($instance);
	kapost_byline_message("Settings successfully updated.");
	return $instance;
}
function kapost_byline_settings_tab($tab=null)
{
	if($tab == null) return (isset($_REQUEST['tab'])?$_REQUEST['tab']:'tab1');
	return ($_REQUEST['tab'] == $tab);
}
function kapost_byline_settings_options() 
{
    if(!current_user_can('manage_options'))  
        wp_die('You do not have sufficient permissions to access this page.');

	$old_instance = kapost_byline_settings();

	echo '<div class="wrap"><h2>Kapost Byline Settings</h2>';
	
	if(isset($_REQUEST['submit']))
		$old_instance = kapost_byline_settings_form_update($_POST[KAPOST_BYLINE_DEFAULT_SETTINGS_KEY], $old_instance);

	$tabs = array("tab1"=>"WordPress","tab2"=>"About");

	$tab = kapost_byline_settings_tab();
	if(!isset($tabs[$tab])) $tab = "tab1";

	foreach($tabs as $t=>$v)
	{
		$selected = ($t == $tab) ? " selected" : "";
		echo  '<a href="'.kapost_byline_settings_url().'&tab='.$t.'" class="kapost-byline-tab'.$selected.'">'.$v.'</a>';
	}

	echo '<div class="kapost-byline-tabbed-settings">';

	switch($tab)
	{
		case 'tab2':
		{
			global $wp_version;
			echo '<div>
					<h3>Version Information:</h3>
					<blockquote>
					<p><strong>Plugin:</strong> '.KAPOST_BYLINE_VERSION.'</p>
					<p><strong>WordPress:</strong> '.$wp_version.'</p>
					<p><strong>PHP:</strong> '.PHP_VERSION.'</p>
					<p><strong>WebServer:</strong> '.$_SERVER['SERVER_SOFTWARE'].'</p>
					</blockquote>
				  </div>';
		}
		break;

		default:
		{
			kapost_byline_settings_form($old_instance);
		}
		break;
	}

	echo '</div>';
}
?>
