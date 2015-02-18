<?php
function kapost_byline_validate_image_url($url)
{
	return preg_match('/^https?:\/\/.*?\/.*?\.(jpg|png|jpeg|bmp|gif)$/', $url);
}

function kapost_byline_extract_filename_from_s3_image_url($url)
{
	$matches = array();

	$re = '/^https:\/\/(.*?)\.s3\.amazonaws\.com\/uploads\/user\/avatar\/.*?\/(.*?)\.(jpg|jpeg|png|bmp|gif)$/';
	if(!preg_match($re, $url, $matches))
		return array();

	return $matches;
}

function kapost_byline_is_s3_cloudfront_installed()
{
	global $aws_meta;
	return isset($aws_meta['amazon-s3-and-cloudfront']);
}

function kapost_byline_get_attachment_by_url($url)
{
	global $wpdb;

	$op = '=';
	$guid = $url;

	if(kapost_byline_is_s3_cloudfront_installed())
	{
		$re = '/^https?:\/\/.*?\/.*?(\/[0-9]{4}\/[0-9]{2}\/.*?)$/';

		if(!preg_match($re, $url, $matches))
			return null;

		$op   = 'LIKE';
		$guid = '%' . $matches[1];
	}

	return $wpdb->get_row($wpdb->prepare("SELECT ID FROM $wpdb->posts 
		WHERE post_type = 'attachment' AND guid " . $op . " %s LIMIT 1", $guid));
}

function kapost_byline_wp_handle_upload($file)
{
	if(isset($file['id']) && kapost_byline_is_s3_cloudfront_installed())
		$file['url'] = wp_get_attachment_url($file['id']);

	return $file;
}

if(defined('XMLRPC_REQUEST'))
	add_filter('wp_handle_upload', 'kapost_byline_wp_handle_upload');
?>
