<?php
require_once('admin.php');

global $bfox_plan;
$bfox_page_url = 'admin.php?page=' . BFOX_MANAGE_PLAN_SUBPAGE;

// TODO: I don't know what these are for
$title = __('Reading Plans');
$parent_file = 'admin.php';

wp_reset_vars(array('action', 'cat'));

if ( isset($_GET['deleteit']) && isset($_GET['delete']) )
	$action = 'bulk-delete';

switch($action) {

case 'addcat':

	check_admin_referer('add-category');

	if ( !current_user_can('manage_categories') )
		wp_die(__('Cheatin&#8217; uh?'));

	if( wp_insert_category($_POST ) ) {
		wp_redirect('categories.php?message=1#addcat');
	} else {
		wp_redirect('categories.php?message=4#addcat');
	}
	exit;
break;

case 'delete':
	$cat_ID = (int) $_GET['cat_ID'];
	check_admin_referer('delete-category_' .  $cat_ID);

	if ( !current_user_can('manage_categories') )
		wp_die(__('Cheatin&#8217; uh?'));

	$cat_name = get_catname($cat_ID);

	// Don't delete the default cats.
    if ( $cat_ID == get_option('default_category') )
		wp_die(sprintf(__("Can&#8217;t delete the <strong>%s</strong> category: this is the default one"), $cat_name));

	wp_delete_category($cat_ID);

	wp_redirect('categories.php?message=2');
	exit;

break;

case 'bulk-delete':
	check_admin_referer('bulk-reading-plans');

	if ( !current_user_can('manage_categories') )
		wp_die( __('You are not allowed to delete reading plans.') );

	foreach ( (array) $_GET['delete'] as $plan_id ) {
		$bfox_plan->delete($plan_id);
	}

	$sendback = wp_get_referer();
	$sendback = preg_replace('|[^a-z0-9-~+_.?#=&;,/:]|i', '', $sendback);

	wp_redirect($sendback);
	exit();

break;
case 'edit':

	require_once ('admin-header.php');
	$cat_ID = (int) $_GET['cat_ID'];
	$category = get_category_to_edit($cat_ID);
	include('edit-category-form.php');

break;

case 'editedcat':
	$cat_ID = (int) $_POST['cat_ID'];
	check_admin_referer('update-category_' . $cat_ID);

	if ( !current_user_can('manage_categories') )
		wp_die(__('Cheatin&#8217; uh?'));

	if ( wp_update_category($_POST) )
		wp_redirect('categories.php?message=3');
	else
		wp_redirect('categories.php?message=5');

	exit;
break;

default:

if ( !empty($_GET['_wp_http_referer']) ) {
	 wp_redirect(remove_query_arg(array('_wp_http_referer', '_wpnonce'), stripslashes($_SERVER['REQUEST_URI'])));
	 exit;
}

wp_enqueue_script( 'admin-categories' );
wp_enqueue_script('admin-forms');

require_once ('admin-header.php');

$messages[1] = __('Category added.');
$messages[2] = __('Category deleted.');
$messages[3] = __('Category updated.');
$messages[4] = __('Category not added.');
$messages[5] = __('Category not updated.');
?>

<?php if (isset($_GET['message'])) : ?>
<div id="message" class="updated fade"><p><?php echo $messages[$_GET['message']]; ?></p></div>
<?php $_SERVER['REQUEST_URI'] = remove_query_arg(array('message'), $_SERVER['REQUEST_URI']);
endif; ?>

<div class="wrap">
<form id="posts-filter" action="" method="get">
<?php if ( current_user_can('manage_categories') ) : ?>
	<h2><?php printf(__('Manage Reading Plans (<a href="%s">add new</a>)'), '#addcat') ?> </h2>
<?php else : ?>
	<h2><?php _e('Manage Reading Plans') ?> </h2>
<?php endif; ?>

<br class="clear" />

<div class="tablenav">

<div class="alignleft">
<input type="submit" value="<?php _e('Delete'); ?>" name="deleteit" class="button-secondary delete" />
<?php wp_nonce_field('bulk-reading-plans'); ?>
</div>

<br class="clear" />
</div>

<br class="clear" />

<table class="widefat">
	<thead>
	<tr>
		<th scope="col" class="check-column"><input type="checkbox" /></th>
        <th scope="col"><?php _e('Name') ?></th>
        <th scope="col"><?php _e('Description') ?></th>
        <th scope="col"><?php _e('Schedules') ?></th>
	</tr>
	</thead>
	<tbody id="the-list" class="list:cat">
<?php
	$plans = $bfox_plan->get_plans();
	foreach ($plans as $plan)
	{
		echo '<tr id="reading-plan-' . $plan->id . '" class="alternate">';
		echo '<th scope="row" class="check-column"> <input type="checkbox" name="delete[]" value="' . $plan->id . '" /></th>';
		echo '<td><a class="row-title" href="' . $bfox_page_url . '&amp;action=edit&amp;plan_id=' . $plan->id . '" title="' .
			attribute_escape(sprintf(__('Edit "%s"'), $plan->name)) . '">' . $plan->name . '</a>';
		echo '<td>' . $plan->summary . '</td>';
		echo '<td>Add a schedule</td>';
		echo '</tr>';
	}
?>
	</tbody>
</table>
</form>

<br class="clear" />

</div>

<?php if ( current_user_can('manage_categories') ) : ?>

<?php include('edit-category-form.php'); ?>

<?php endif; ?>

<?php
break;
}

include('admin-footer.php');

?>