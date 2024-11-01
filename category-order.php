<?php
/*
Plugin Name: Order Categories widget
Plugin URI: http://newplugins.us/wp-order-categories-widget/
Description: Order Categories widget allows you to set the order in which categories will appear in the sidebar. Uses a drag and drop interface for ordering. Adds a widget with additional options for easy installation on widgetized themes.
Version: 2.0
Author: Sanauna
Author URI: http://newplugins.us/

*/



function ordercategory_init() {

function ordercategory_menu()
{   
	 add_posts_page(__('Order Categories widget', 'ordercategory'), __('Order Categories widget', 'ordercategory'), 'manage_categories', 'ordercategory', 'ordercategory');
}

function ordercategory_js_libs() {
	if ( isset($_GET['page']) && $_GET['page'] == "ordercategory" )
	{	
		wp_enqueue_script('jquery');
		wp_enqueue_script('jquery-ui-core');
		wp_enqueue_script('jquery-ui-sortable');
	}
}


function ordercategory_getTarget() {
	return "edit.php";
}

function ordercategory_set_plugin_meta($links, $file) {
	$plugin = plugin_basename(__FILE__);
	// create link
	if ($file == $plugin) {
		return array_merge( $links, array( 

		));
	}
	return $links;
}

add_filter('plugin_row_meta', 'ordercategory_set_plugin_meta', 10, 2 );
add_action('admin_menu', 'ordercategory_menu');
add_action('admin_print_scripts', 'ordercategory_js_libs');

function ordercategory()
{
	global $wpdb;
	
	$parentID = 0;
	
	$wpdb->show_errors();

	$query1 = $wpdb->query("SHOW COLUMNS FROM $wpdb->terms LIKE 'term_order'");
	
	if ($query1 == 0) {
		$wpdb->query("ALTER TABLE $wpdb->terms ADD `term_order` INT( 4 ) NULL DEFAULT '0'");
	}
	
	if (isset($_POST['btnSubCats'])) { 
		$parentID = $_POST['cats'];
	}
	elseif (isset($_POST['hdnParentID'])) { 
		$parentID = $_POST['hdnParentID'];
	}

	if (isset($_POST['btnReturnParent'])) { 
		$parentsParent = $wpdb->get_row("SELECT parent FROM $wpdb->term_taxonomy WHERE term_id = " . $_POST['hdnParentID'], ARRAY_N);
		$parentID = $parentsParent[0];
	}
		
	if(isset($_GET['hideNote']))
		update_option('ordercategory_hideNote', '1');

	$success = "";
	if (isset($_POST['btncategoryor'])) { 
		$success = ordercategory_updateOrder();
	}

	$subCatStr = ordercategory_getSubCats($parentID);
	
?>
<div class='wrap'>
<form name="frmMyCatOrder" method="post" action="">
		<h2><?php _e('Order Categories widget','ordercategory'); ?></h2>
	<?php 
	
	echo $success; 
	
	if (get_option("ordercategory_hideNote") != "1")
		{	?>

		<?php
		}?>

    // class pro cres

	<p><?php _e('Choose a category from the drop down to order subcategories in that category <br />or order the categories on this level by dragging and dropping them into the desired order.','ordercategory'); ?></p>

<?php 
	if($subCatStr != "")
	{ 
	?>
	<h3><?php _e('Order Subcategories','ordercategory'); ?></h3>
	<select id="cats" name="cats">
		<?php echo $subCatStr; ?>
	</select>
	&nbsp;<input type="submit" name="btnSubCats" class="button" id="btnSubCats" value="<?php _e('Order Subcategories','ordercategory'); ?>" />
	<?php } ?>
	
	<h3><?php _e('Order Categories','ordercategory'); ?></h3>
	<ul id="ordercategoryList">
	<?php 
	$results= ordercategory_catQuery($parentID);
	foreach($results as $row)
		echo "<li id='id_$row->term_id' class='lineitem'>".__($row->name)."</li>";
	?>
	</ul>

	<input type="submit" name="btncategoryor" id="btncategoryor" class="button-primary" value="<?php _e('Click to Order Categories', 'ordercategory') ?>" onclick="javascript:categoryor(); return true;" />
	<?php echo ordercategory_getParentLink($parentID); ?>
	&nbsp;&nbsp;<strong id="updateText"></strong>
	<br /><br />

	<input type="hidden" id="hdnordercategory" name="hdnordercategory" />
	<input type="hidden" id="hdnParentID" name="hdnParentID" value="<?php echo $parentID; ?>" />
</form>
</div>

<style type="text/css">
	#ordercategoryList {
		width: 90%; 
		border:1px solid #B2B2B2; 
		margin:10px 10px 10px 0px;
		padding:5px 10px 5px 10px;
		list-style:none;
		background-color:#fff;
		-moz-border-radius:3px;
		-webkit-border-radius:3px;
	}

	li.lineitem {
		border:1px solid #B2B2B2;
		-moz-border-radius:3px;
		-webkit-border-radius:3px;
		background-color:#F1F1F1;
		color:#000;
		cursor:move;
		font-size:13px;
		margin-top:5px;
		margin-bottom:5px;
		padding: 2px 5px 2px 5px;
		height:1.5em;
		line-height:1.5em;
	}
	
	.sortable-placeholder{ 
		border:1px dashed #B2B2B2;
		margin-top:5px;
		margin-bottom:5px; 
		padding: 2px 5px 2px 5px;
		height:1.5em;
		line-height:1.5em;	
	}
</style>

<script type="text/javascript">


	function categoryorderaddloadevent(){
		jQuery("#ordercategoryList").sortable({ 
			placeholder: "sortable-placeholder", 
			revert: false,
			tolerance: "pointer" 
		});
	};

	addLoadEvent(categoryorderaddloadevent);
	
	function categoryor() {
		jQuery("#updateText").html("<?php _e('Updating Category Order...', 'ordercategory') ?>");
		jQuery("#hdnordercategory").val(jQuery("#ordercategoryList").sortable("toArray"));
	}

// ]]>
</script>

<?php
}
}

function ordercategory_getSubCats($parentID)
{
	global $wpdb;
	
	$subCatStr = "";
	$results=$wpdb->get_results("SELECT t.term_id, t.name FROM $wpdb->term_taxonomy tt, $wpdb->terms t, $wpdb->term_taxonomy tt2 WHERE tt.parent = $parentID AND tt.taxonomy = 'category' AND t.term_id = tt.term_id AND tt2.parent = tt.term_id GROUP BY t.term_id, t.name HAVING COUNT(*) > 0 ORDER BY t.term_order ASC");
	foreach($results as $row)
	{
		$subCatStr = $subCatStr."<option value='$row->term_id'>$row->name</option>";
	}

	return $subCatStr;
}

function ordercategory_updateOrder()
{
	if (isset($_POST['hdnordercategory']) && $_POST['hdnordercategory'] != "") { 
		global $wpdb;
		
		$hdnordercategory = $_POST['hdnordercategory'];
		$IDs = explode(",", $hdnordercategory);
		$result = count($IDs);

		for($i = 0; $i < $result; $i++)
		{
			$str = str_replace("id_", "", $IDs[$i]);
			$wpdb->query("UPDATE $wpdb->terms SET term_order = '$i' WHERE term_id ='$str'");
		}

		return '<div id="message" class="updated fade"><p>'. __('Categories updated successfully.', 'ordercategory').'</p></div>';
	}
	else
		return '<div id="message" class="updated fade"><p>'. __('An error occured, order has not been saved.', 'ordercategory').'</p></div>';
}

function ordercategory_catQuery($parentID)
{
	global $wpdb;
	return $wpdb->get_results("SELECT * FROM $wpdb->terms t inner join $wpdb->term_taxonomy tt on t.term_id = tt.term_id WHERE taxonomy = 'category' and parent = $parentID ORDER BY term_order ASC");
}

function  ordercategory_getParentLink($parentID)
{
	if($parentID != 0)
		return "&nbsp;&nbsp;<input type='submit' class='button' id='btnReturnParent' name='btnReturnParent' value='" . __('Return to parent category', 'ordercategory') ."' />";
	else
		return "";
}

function ordercategory_applyorderfilter($orderby, $args)
{
	if($args['orderby'] == 'order')
		return 't.term_order';
	else
		return $orderby;
}

add_filter('get_terms_orderby', 'ordercategory_applyorderfilter', 10, 2);

add_action('plugins_loaded', 'ordercategory_init');


add_action('init', 'ordercategory_loadtranslation');

function ordercategory_loadtranslation() {
	load_plugin_textdomain('ordercategory', PLUGINDIR.'/'.dirname(plugin_basename(__FILE__)), dirname(plugin_basename(__FILE__)));
}


class ordercategory_Widget extends WP_Widget {

	function ordercategory_Widget() {
		$widget_ops = array('classname' => 'widget_ordercategory', 'description' => __( 'Enhanced Category widget provided by WP Order Categories widget') );
		$this->WP_Widget('ordercategory', __('WP Order Categories widget'), $widget_ops);	}

	function widget( $args, $instance ) {
		extract( $args );

		$title_li = apply_filters('widget_title', empty( $instance['title_li'] ) ? __( 'Categories' ) : $instance['title_li']);
		$orderby = empty( $instance['orderby'] ) ? 'order' : $instance['orderby'];
		$order = empty( $instance['order'] ) ? 'asc' : $instance['order'];
		$show_dropdown = (bool) $instance['show_dropdown'];
		$show_last_updated = (bool) $instance['show_last_updated'];
		$show_count = (bool) $instance['show_count'];
		$hide_empty = (bool) $instance['hide_empty'];
		$use_desc_for_title = (bool) $instance['use_desc_for_title'];
		$child_of = empty( $instance['child_of'] ) ? '' : $instance['child_of'];
		$feed = empty( $instance['feed'] ) ? '' : $instance['feed'];
		$feed_image = empty( $instance['feed_image'] ) ? '' : $instance['feed_image'];
		$exclude = empty( $instance['exclude'] ) ? '' : $instance['exclude'];
		$exclude_tree = empty( $instance['exclude_tree'] ) ? '' : $instance['exclude_tree'];
		$include = empty( $instance['include'] ) ? '' : $instance['include'];
		$hierarchical = empty( $instance['hierarchical'] ) ? '1' : $instance['hierarchical'];
		$number = empty( $instance['number'] ) ? '' : $instance['number'];
		$depth = empty( $instance['depth'] ) ? '0' : $instance['depth'];

		echo $before_widget;
		if ( $title_li )
			echo $before_title . $title_li . $after_title;

		$cat_args = array('orderby' => $orderby, 'order' => $order, 'show_last_updated' => $show_last_updated, 'show_count' => $show_count, 
			'hide_empty' => $hide_empty, 'use_desc_for_title' => $use_desc_for_title, 'child_of' => $child_of, 'feed' => $feed, 
			'feed_image' => $feed_image, 'exclude' => $exclude, 'exclude_tree' => $exclude_tree, 'include' => $include,
			'hierarchical' => $hierarchical, 'number' => $number, 'depth' => $depth,  );

		if ( $show_dropdown ) {
			static $dropdown_count = 0;

			$cat_id = 'dropdown_'.$args['widget_id'];
			$cat_args['id'] = $cat_args['name'] = $cat_id;
			$cat_args['show_option_none'] = __('Select Category');
			wp_dropdown_categories(apply_filters('widget_categories_dropdown_args', $cat_args));
?>

<script type='text/javascript'>

<?php if ( $dropdown_count == 0 ) { ?>
	function cate_goryor( dropdownID ) {
		var dropdown = document.getElementById(dropdownID);
		if ( dropdown.options[dropdown.selectedIndex].value > 0 ) {
			location.href = "<?php echo home_url(); ?>/?cat="+dropdown.options[dropdown.selectedIndex].value;
		}
	}
<?php } ?>
	document.getElementById("<?php echo $cat_id; ?>").onchange = function(){cate_goryor(this.id)};

</script>

<?php
		$dropdown_count++;
		} else {
		?>
		<ul>
		<?php
		$cat_args['title_li'] = '';
		wp_list_categories(apply_filters('widget_categories_args', $cat_args));
		?>
		</ul>
		<?php
		}

		echo $after_widget;
	}

	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;

		if ( in_array( $new_instance['orderby'], array( 'order', 'name', 'count', 'ID', 'slug', 'term_group' ) ) ) {
			$instance['orderby'] = $new_instance['orderby'];
		} else {
			$instance['orderby'] = 'order';
		}
		
		if ( in_array( $new_instance['order'], array( 'asc', 'desc' ) ) ) {
			$instance['order'] = $new_instance['order'];
		} else {
			$instance['order'] = 'asc';
		}
		
		$instance['title_li'] = strip_tags( $new_instance['title_li'] );	
		$instance['show_dropdown'] = strip_tags( $new_instance['show_dropdown'] );
		$instance['show_last_updated'] = strip_tags( $new_instance['show_last_updated'] );
		$instance['show_count'] = strip_tags( $new_instance['show_count'] );
		$instance['hide_empty'] = strip_tags( $new_instance['hide_empty'] );
		$instance['use_desc_for_title'] = strip_tags( $new_instance['use_desc_for_title'] );
		$instance['child_of'] = strip_tags( $new_instance['child_of'] );
		$instance['feed'] = strip_tags( $new_instance['feed'] );
		$instance['feed_image'] = $new_instance['feed_image'];
		$instance['exclude'] = strip_tags( $new_instance['exclude'] );
		$instance['exclude_tree'] = strip_tags( $new_instance['exclude_tree'] );
		$instance['include'] = strip_tags( $new_instance['include'] );
		$instance['hierarchical'] = strip_tags( $new_instance['hierarchical'] );
		$instance['number'] = $new_instance['number'];
		$instance['depth'] = $new_instance['depth'];

		return $instance;
	}
	
	function form( $instance ) {
		//Defaults
		$instance = wp_parse_args( (array) $instance, array( 'orderby' => 'order', 'order' => 'asc', 'title_li' => '', 'show_dropdown' => '', 'show_last_updated' => '', 'show_count' => '', 'hide_empty' => '1', 'use_desc_for_title' => '1', 'child_of' => '', 'feed' => '', 'feed_image' => '', 'exclude' => '', 'exclude_tree' => '', 'include' => '', 'hierarchical' => '1', 'number' => '', 'depth' => '' ) );
		
		$orderby = esc_attr( $instance['orderby'] );
		$order = esc_attr( $instance['order'] );
		$title_li = esc_attr( $instance['title_li'] );
		
		$show_dropdown = esc_attr( $instance['show_dropdown'] );
		$show_last_updated = esc_attr( $instance['show_last_updated'] );
		$show_count = esc_attr( $instance['show_count'] );
		$hide_empty = esc_attr( $instance['hide_empty'] );
		$use_desc_for_title = esc_attr( $instance['use_desc_for_title'] );
		$hierarchical = esc_attr( $instance['hierarchical'] );
		
		$child_of = esc_attr( $instance['child_of'] );
		$feed = esc_attr( $instance['feed'] );
		$feed_image = esc_attr( $instance['feed_image'] );
		$exclude = esc_attr( $instance['exclude'] );
		$exclude_tree = esc_attr( $instance['exclude_tree'] );
		$include = esc_attr( $instance['include'] );
		
		$number = esc_attr( $instance['number'] );
		$depth  = esc_attr( $instance['depth'] );

	?>	
		<p>
			<label for="<?php echo $this->get_field_id('orderby'); ?>"><?php _e( 'Order By:', 'ordercategory' ); ?></label>
			<select name="<?php echo $this->get_field_name('orderby'); ?>" id="<?php echo $this->get_field_id('orderby'); ?>" class="widefat">
				<option value="order"<?php selected( $instance['orderby'], 'order' ); ?>><?php _e('My Order', 'ordercategory'); ?></option>
				<option value="name"<?php selected( $instance['orderby'], 'name' ); ?>><?php _e('Name', 'ordercategory'); ?></option>
				<option value="count"<?php selected( $instance['orderby'], 'count' ); ?>><?php _e( 'Count', 'ordercategory' ); ?></option>
				<option value="ID"<?php selected( $instance['orderby'], 'ID' ); ?>><?php _e( 'ID', 'ordercategory' ); ?></option>
				<option value="slug"<?php selected( $instance['orderby'], 'slug' ); ?>><?php _e( 'Slug', 'ordercategory' ); ?></option>
				<option value="term_group"<?php selected( $instance['orderby'], 'term_group' ); ?>><?php _e( 'Term Group', 'ordercategory' ); ?></option>
			</select>
		</p>
		<p>
			<label for="<?php echo $this->get_field_id('order'); ?>"><?php _e( 'Order:', 'ordercategory' ); ?></label>
			<select name="<?php echo $this->get_field_name('order'); ?>" id="<?php echo $this->get_field_id('category_order'); ?>" class="widefat">
				<option value="asc"<?php selected( $instance['order'], 'asc' ); ?>><?php _e('Ascending', 'ordercategory'); ?></option>
				<option value="desc"<?php selected( $instance['order'], 'desc' ); ?>><?php _e('Descending', 'ordercategory'); ?></option>
			</select>
		</p>
				<p>
			<label for="<?php echo $this->get_field_id('title_li'); ?>"><?php _e( 'Title:', 'ordercategory' ); ?></label> <input type="text" value="<?php echo $title_li; ?>" name="<?php echo $this->get_field_name('title_li'); ?>" id="<?php echo $this->get_field_id('title_li'); ?>" class="widefat" />
			<br />
			<small><?php _e( 'Default to Categories.', 'ordercategory' ); ?></small>
		</p>
		<p>
			<label for="<?php echo $this->get_field_id('exclude'); ?>"><?php _e( 'Exclude:', 'ordercategory' ); ?></label> <input type="text" value="<?php echo $exclude; ?>" name="<?php echo $this->get_field_name('exclude'); ?>" id="<?php echo $this->get_field_id('exclude'); ?>" class="widefat" />
			<br />
			<small><?php _e( 'Category IDs, separated by commas.', 'ordercategory'  ); ?></small>
		</p>
		<p>
			<label for="<?php echo $this->get_field_id('exclude_tree'); ?>"><?php _e( 'Exclude Tree:', 'ordercategory' ); ?></label> <input type="text" value="<?php echo $exclude_tree; ?>" name="<?php echo $this->get_field_name('exclude_tree'); ?>" id="<?php echo $this->get_field_id('exclude_tree'); ?>" class="widefat" />
			<br />
			<small><?php _e( 'Category IDs, separated by commas.', 'ordercategory'  ); ?></small>
		</p>
		<p>
			<label for="<?php echo $this->get_field_id('include'); ?>"><?php _e( 'Include:', 'ordercategory' ); ?></label> <input type="text" value="<?php echo $include; ?>" name="<?php echo $this->get_field_name('include'); ?>" id="<?php echo $this->get_field_id('include'); ?>" class="widefat" />
			<br />
			<small><?php _e( 'Category IDs, separated by commas.', 'ordercategory'  ); ?></small>
		</p>



		<p>
			<label for="<?php echo $this->get_field_id('number'); ?>"><?php _e( 'Number to Display:', 'ordercategory' ); ?></label> <input type="text" value="<?php echo $number; ?>" name="<?php echo $this->get_field_name('number'); ?>" id="<?php echo $this->get_field_id('number'); ?>" class="widefat" />
			<br />
			<small><?php _e( 'Max number of categories to display', 'ordercategory'  ); ?></small>
		</p>

		<p>
			<input class="checkbox" type="checkbox" <?php checked( (bool) $instance['show_dropdown'], true) ?> id="<?php echo $this->get_field_id('show_dropdown'); ?>" name="<?php echo $this->get_field_name('show_dropdown'); ?>" />
			<label for="<?php echo $this->get_field_id('show_dropdown'); ?>"><?php _e('Show As Dropdown', 'ordercategory'); ?></label><br />
			<input class="checkbox" type="checkbox" <?php checked( (bool) $instance['show_last_updated'], true) ?> id="<?php echo $this->get_field_id('show_last_updated'); ?>" name="<?php echo $this->get_field_name('show_last_updated'); ?>" />
			<label for="<?php echo $this->get_field_id('show_last_updated'); ?>"><?php _e('Show Last Updated', 'ordercategory'); ?></label><br />
			<input class="checkbox" type="checkbox" <?php checked( (bool) $instance['show_count'], true) ?> id="<?php echo $this->get_field_id('show_count'); ?>" name="<?php echo $this->get_field_name('show_count'); ?>" />
			<label for="<?php echo $this->get_field_id('show_count'); ?>"><?php _e('Show Count', 'ordercategory'); ?></label><br />
			<input class="checkbox" type="checkbox" <?php checked( (bool) $instance['hide_empty'], true) ?> id="<?php echo $this->get_field_id('hide_empty'); ?>" name="<?php echo $this->get_field_name('hide_empty'); ?>" />
			<label for="<?php echo $this->get_field_id('hide_empty'); ?>"><?php _e('Hide Empty', 'ordercategory'); ?></label><br />
			<input class="checkbox" type="checkbox" <?php checked( (bool) $instance['use_desc_for_title'], true) ?> id="<?php echo $this->get_field_id('use_desc_for_title'); ?>" name="<?php echo $this->get_field_name('use_desc_for_title'); ?>" />
			<label for="<?php echo $this->get_field_id('use_desc_for_title'); ?>"><?php _e('Use Desc as Title', 'ordercategory'); ?></label><br />
			<input class="checkbox" type="checkbox" <?php checked( (bool) $instance['hierarchical'], true) ?> id="<?php echo $this->get_field_id('hierarchical'); ?>" name="<?php echo $this->get_field_name('hierarchical'); ?>" />
			<label for="<?php echo $this->get_field_id('hierarchical'); ?>"><?php _e('Show Hierarchical', 'ordercategory'); ?></label><br />
		</p>
<?php
	}
}

function ordercategory_widgets_init() {
	register_widget('ordercategory_Widget');
}

add_action('widgets_init', 'ordercategory_widgets_init');

?>