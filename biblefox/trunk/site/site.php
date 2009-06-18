<?php

define(BFOX_SITE_DIR, dirname(__FILE__));

include_once BFOX_SITE_DIR . '/wordpress-admin-bar/wordpress-admin-bar.php';
include_once BFOX_SITE_DIR . '/wp-hashcash/wp-hashcash.php';
include_once BFOX_SITE_DIR . '/marketing.php';
include_once BFOX_SITE_DIR . '/shortfoot.php';

class BiblefoxSite {

	const manage_trans_page = 'bfox-translations';
	const manage_trans_min_user_level = 10;

	/**
	 * Returns the bible study blogs for a given user
	 *
	 * Should be used in place of get_blogs_of_user() because the main biblefox.com blog should not count
	 *
	 * @param integer $user_id
	 * @return array of blogs (see get_blogs_of_user())
	 */
	public static function get_bible_study_blogs($user_id) {
		// Get the blogs for the user
		$blogs = get_blogs_of_user($user_id);

		// The main biblefox blog does not count as a bible study blog
		unset($blogs[1]);

		return $blogs;
	}

	public static function new_blog_settings($blog_id, $user_id) {
		global $wpdb;

		switch_to_blog($blog_id);

		// Change the old Wordpress.com link to Biblefox.com
		$wpdb->query($wpdb->prepare("UPDATE $wpdb->links
			SET link_url = %s, link_name = %s, link_rss = %s
			WHERE link_id = 1",
			'http://biblefox.com/',
			'Biblefox.com',
			'http://biblefox.com/feed/'));

		// Change the old Wordpress.org link to Biblefox.com/bible/
		// TODO3: actually implement bible feeds at /bible/feed/
		$wpdb->query($wpdb->prepare("UPDATE $wpdb->links
			SET link_url = %s, link_name = %s, link_rss = %s
			WHERE link_id = 2",
			'http://biblefox.com/bible/',
			'Bible Reader',
			'http://biblefox.com/bible/feed/'));

		restore_current_blog();
	}

	/**
	 * This returns a link for logging in or for logging out
	 *
	 * It always goes to the login page for the main blog and redirects back to the page from which it was called.
	 * This gives the whole site a common login place that seamlessly integrates with every blog.
	 *
	 * @return unknown
	 */
	public static function loginout() {
		// From auth_redirect()
		if ( is_ssl() )
			$proto = 'https://';
		else
			$proto = 'http://';

		$old_url = urlencode($proto . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);

		// From site_url()
		$site_url = 'http';
		if (force_ssl_admin()) $site_url .= 's'; // Use https

		// Always use the main blog for login/out
		global $current_blog;
		$site_url .= '://' . $current_blog->domain . $current_blog->path . 'wp-login.php?';

		// From wp_loginout()
		if (!is_user_logged_in())
			$link = '<a href="' . $site_url . 'redirect_to=' . $old_url . '">' . __('Log in') . '</a>';
		else
			$link = '<a href="' . wp_logout_url($old_url) . '">' . __('Log out') . '</a>';

		return $link;
	}

	private static function admin_bar_dropdown($title, $url = '') {
		return "<a href='$url'><span class='wpabar-dropdown'>$title</span></a>";
	}

	public static function admin_bar() {
		global $current_site, $current_blog, $current_user, $user_ID;

		$login = BiblefoxSite::loginout();

		$left_side = new BfoxHtmlList();
		$right_side = new BfoxHtmlList();
		if (!empty($user_ID)) {
			$user_list = new BfoxHtmlList();
			$dashboards = new BfoxHtmlList();
			$new_posts = new BfoxHtmlList();
			$blog_options = new BfoxHtmlList();

			$active_blog = get_active_blog_for_user($user_ID);
			if (is_object($active_blog)) $default_url = $active_blog->siteurl . '/';
			else $default_url = 'http://' . $current_site->domain . $current_site->path;

			$current_url = 'http://' . $current_blog->domain . $current_blog->path;

			$profile_url = $default_url . 'wp-admin/profile.php';
			$dashboards_url = $default_url . 'wp-admin/';
			$new_posts_url = $default_url . 'wp-admin/post-new.php';
			$my_blogs_url = $default_url. 'wp-admin/blogs.php';

			$blogs = get_blogs_of_user($user_ID);
			$blog_count = count($blogs);
			foreach ($blogs as $blog) {
				$dashboards->add("<a href='$blog->siteurl/wp-admin/'>$blog->blogname</a>");
				$new_posts->add("<a href='$blog->siteurl/wp-admin/post-new.php'>$blog->blogname</a>");
			}

			$user_list->add("<a href='$my_blogs_url'>My Blogs</a>");
			$user_list->add("<a href='$profile_url'>Edit Profile</a>");
			$user_list->add("<a href='http://biblefox.com'>Biblefox.com</a>");
			$user_list->add($login);

			$add_url = add_query_arg('add_url', $current_url, BfoxQuery::page_url(BfoxQuery::page_commentary));
			$blog_options->add("<a href='$add_url'>Subscribe to Bible Feed</a>");
			// TODO3: Report as spam and mature
			//$blog_options->add("<a href='$add_url'>Report as spam</a>");
			//$blog_options->add("<a href='$add_url'>Report as mature</a>");

			$drop_class = "class='wpabar-menupop' onmouseover='showNav(this)' onmouseout='hideNav(this)'";
			$left_side->add(self::admin_bar_dropdown($current_user->user_login, $profile_url) . $user_list->content(), $drop_class);
			if (1 < $blog_count) {
				$left_side->add(self::admin_bar_dropdown(__('My Dashboards'), $dashboards_url) . $dashboards->content(), $drop_class);
				$left_side->add(self::admin_bar_dropdown(__('New Post'), $new_posts_url) . $new_posts->content(), $drop_class);
			}
			elseif (0 < $blog_count) {
				$left_side->add("<a href='$dashboards_url'>My Dashboard</a>");
				$left_side->add("<a href='$new_posts_url'>New Post</a>");
			}
			$right_side->add(self::admin_bar_dropdown(__('Blog Options')) . $blog_options->content(), $drop_class);
		}
		else {
			$left_side->add($login);
			$left_side->add("<a href='http://" . $current_site->domain . $current_site->path . "wp-signup.php'>" . __('Sign Up') . "</a>");
		}

		return array($left_side->content(), $right_side->content());
	}

	public static function query_vars($qvars) {
		// Add a query variable for bible references
		$qvars []= BfoxQuery::var_page;
		$qvars []= BfoxQuery::var_pretty_query;
		return $qvars;
	}

	public static function parse_request(WP $wp) {
		// We don't need wp_query for the bible viewer, so we can exit at the end of request parsing, before wp_query is called
		if (isset($wp->query_vars[BfoxQuery::var_page]) || isset($wp->query_vars[BfoxQuery::var_pretty_query])) {
			global $current_blog, $current_site, $user_ID;

			// The bible should always be on the main blog, so if it isn't just redirect it
			if (is_main_blog()) {
				if (empty($user_ID)) auth_redirect();

				require_once BFOX_DIR . '/bible/bible.php';
				$bible = new BfoxBible($wp->query_vars[BfoxQuery::var_pretty_query]);
				$bible->page();
			}
			else wp_redirect((is_ssl() ? 'https://' : 'http://') . $current_site->domain . $current_site->path . substr($_SERVER['REQUEST_URI'], strlen($current_blog->path)));
			exit;
		}
	}

	public static function widget_bible_pages($args) {
		extract($args);
		$title = "<a href='" . BfoxQuery::page_url(BfoxQuery::page_passage) . "'>Bible Viewer</a>";
		echo $before_widget . $before_title . $title . $after_title;
		echo $after_widget;
	}

	public static function wpabar_defaults($defaults) {
		$defaults['show_admin'] = 1;
		return $defaults;
	}

	public static function init() {
		add_filter('query_vars', 'BiblefoxSite::query_vars');
		add_action('parse_request', 'BiblefoxSite::parse_request');
		register_sidebar_widget('Bible Pages', array('BiblefoxSite', 'widget_bible_pages'));
		add_action('wpmu_new_blog', 'BiblefoxSite::new_blog_settings', 10, 2);
		wp_deregister_style('login');
		BfoxUtility::register_style('login', 'site/login.css');
	}
}
add_action('init', 'BiblefoxSite::init');
add_filter('wpabar_defaults', 'BiblefoxSite::wpabar_defaults');


/**
 * Filter function for changine the email from name to Biblefox
 *
 * @param string $from_name
 * @return string
 */
function bfox_wp_mail_from_name($from_name)
{
	if ('WordPress' == $from_name) $from_name = 'Biblefox';
	return $from_name;
}
add_filter('wp_mail_from_name', 'bfox_wp_mail_from_name');

/**
 * Filter function for allowing page titles to be used in the exclude parameter passed to wp_list_pages()
 *
 * TODO2: See if we still need this function
 *
 * @param unknown_type $excludes
 * @return unknown
 */
function bfox_list_pages_excludes($excludes)
{
	global $wpdb;

	// Convert any string title excludes to a post id
	// wpdb query from get_page_by_title()
	foreach ($excludes as &$exclude)
		if (!is_integer($exclude))
			$exclude = $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE post_title = %s AND post_type='page'", $exclude ));

	return $excludes;
}
add_filter('wp_list_pages_excludes', 'bfox_list_pages_excludes');

?>