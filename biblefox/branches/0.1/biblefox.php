<?php
	/*
	 Plugin Name: biblefox
	 Plugin URI: http://tools.biblefox.com/
	 Description: Allows your blog to become a bible commentary, and adds the entire bible text to your blog, so you can read, search, and study the bible all from your blog.
	 Version: 0.1
	 Author: Biblefox
	 Author URI: http://biblefox.com
	 */

	define(BFOX_FILE, __FILE__);
	define(BFOX_DIR, dirname(__FILE__));
	define(BFOX_DATA_DIR, BFOX_DIR . '/data');

	define(BFOX_ADMIN_FILE, '../wp-content/mu-plugins/biblefox/biblefox.php');
	define(BFOX_DOMAIN, 'biblefox');

	define(BFOX_BASE_TABLE_PREFIX, $GLOBALS['wpdb']->base_prefix . 'bfox_');

	require_once BFOX_DIR . '/biblerefs/ref.php';
	require_once BFOX_DIR . '/utility.php';
	require_once BFOX_DIR . '/query.php';

	include_once BFOX_DIR . '/translations/bfox-translations.php';
	include_once BFOX_DIR . '/admin/admin-tools.php';
	include_once BFOX_DIR . '/blog/blog.php';
	include_once BFOX_DIR . '/bible/load.php';
	include_once BFOX_DIR . '/site/site.php';

	// TODO3: These files are probably obsolete
	require_once BFOX_DIR . '/site/message.php';

	/**
	 * Hacky function for showing DB errors. Hacked into the wpdb query filter to make sure that the errors always show.
	 *
	 * @param unknown_type $query
	 * @return unknown
	 */
	function bfox_show_errors($query)
	{
		global $wpdb;
		define('DIEONDBERROR', 'die!');
		$wpdb->show_errors(true);
		return $query;
	}
	if (defined('BFOX_TESTBED')) add_filter('query', 'bfox_show_errors');

	// TODO3: come up with something else
	function pre($expr) { echo '<pre>'; print_r($expr); echo '</pre>'; }

	function biblefox_init()
	{
		BfoxQuery::set_url(get_option('home') . '/?');
		bfox_query_init();
		bfox_widgets_init();
	}

	add_action('init', 'biblefox_init');

?>