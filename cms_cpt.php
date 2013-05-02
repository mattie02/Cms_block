<?php
/*
Plugin Name: CMS cpt
Plugin URI: http://flightofthedodo.com/
Description: Creates a cutst
Version: 1.0 beta
Author: Matthew Hansen
Author URI: http://flightofthedodo.com/
License: GPLv2
*/

// CREATE CUSTOM POST TYPES

add_action( 'init', 'mrh_create_cms_type' );


function mrh_create_cms_type() {
register_post_type( 'cms_cpt',
array(
'labels' => array(
'name' => 'CMS',
'singular_name' => 'CMS',
'add_new' => 'Add New',
'add_new_item' => 'Add New CMS block',
'edit' => 'Edit',
'edit_item' => 'Edit CMS block',
'new_item' => 'New MCMS block',
'view' => 'View',
'view_item' => 'View CMS blocks',
'search_items' => 'Search CMS blocks',
'not_found' => 'No CMS blocks found',
'not_found_in_trash' =>
'No CMS blocks found in Trash',
'parent' => 'Parent CMS block'
),
'public' => true,
'menu_position' => 4,
'supports' =>
array( 'title', 'editor', 'thumbnail', 'excerpt' ),
'taxonomies' => array( '' )
)
);

}

add_action( 'init', 'mrh_create_my_taxonomies', 0 );

function mrh_create_my_taxonomies() {
	register_taxonomy( 
		'cms_block_page',
		'cms_cpt',
		array(
			'labels' => array(
				'name' => 'On Page',
				'add_new_item' => 'Add New Page',
				'new_item_name' => 'New Page Classification'				
			), 
		'show_ui' => true,
		'show_tagcloud' => false, 
		'hierarchical' => true
		)
		
	);
}

add_action ('admin_init', 'mrh_my_admin'); 

function mrh_my_admin() {
	add_meta_box( 'block_information_meta_box', 
		'Block Information', 
		'display_cms_cpt_information_meta_box',
		'cms_cpt', 'normal', 'low'
	);
}

function display_cms_cpt_information_meta_box( $cpt_cms ) {
	$position = esc_html( get_post_meta( $cpt_cms->ID, 'cpt_cms_position', true ) );
	$description = esc_html( get_post_meta( $cpt_cms->ID, 'cpt_cms_description', true ) );
?>
	<h4>Page Postition</h4>
	<input type="text" name="cpt_cms_position" value="<?php echo $position; ?>">
	<p>Location of the Block on the page</p>
	<h4>Description</h4>
	<textarea id="excerpt" name="cpt_cms_description"/><?php echo $description; ?></textarea>
	<h4>Shortcode Template (Pages, Posts)</h4>
	<p>[ocg_block slug="<strong>PAGE SLUG</strong>" value="<strong>ATTRIBUTE</strong>"]<p>
	<h4>PAGE SLUG</h4>
	<p>Permalink: http://matt.dev/plugin/?cms_cpt=<strong>SLUG</strong></p>
	<h4>ATTRIBUTES</h4>
	<ul>
		<li><strong>post_title</strong> - Pulls the title of the post</li>
		<li><strong>post_content</strong> - Pulls the main content of the post</li>
		<li><strong>post_excerpt</strong> - Pulls the excerpt of the post</li>
		<li><strong>thumbnail</strong> - Pulls the SRC of the post featured image</li>
	</ul>	
<?php }

add_action( 'save_post', 'mrh_add_cms_cpt_description_field', 10, 2 );

function mrh_add_cms_cpt_description_field( $cms_cpt_id, $cms_cpt ) {
	if ( $cms_cpt->post_type == 'cms_cpt' ) {
		if ( isset( $_POST['cpt_cms_description'] ) && $_POST['cpt_cms_description'] != '') {
			update_post_meta( $cms_cpt_id, 'cpt_cms_description', $_POST['cpt_cms_description'] );
		}
		if ( isset( $_POST['cpt_cms_position'] ) && $_POST['cpt_cms_position'] != '') {
			update_post_meta( $cms_cpt_id, 'cpt_cms_position', $_POST['cpt_cms_position'] );
		}
	}
}

add_filter( 'manage_edit-cms_cpt_columns', 'mrh_my_edit_cms_columns' );

function mrh_my_edit_cms_columns( $columns ) {

	$columns = array(
		'cb' => '<input type="checkbox" />',
		'title' => __( 'title' ),
		'page' => __( 'On Page' ),
		'position' => __( 'Page Position' ),
		'date' => __( 'Date' )
	);

	return $columns;
}

add_action( 'manage_cms_cpt_posts_custom_column', 'mrh_my_manage_cms_columns', 10, 2 );

function mrh_my_manage_cms_columns( $columns, $post_id ) {
	global $post;

	switch( $columns ) {
		case "page":
			echo get_the_term_list($post->ID, 'cms_block_page', '', ', ','');
			break;
		case "position":
			echo get_post_meta($post->ID, 'cpt_cms_position', true);
		 	break;
	}
}

add_filter( 'manage_edit-cms_cpt_sortable_columns', 'mrh_sort_me');

function mrh_sort_me( $columns ) {
	$columns['page'] = 'page';
	$columns['position'] = 'position';
	return $columns;
}


add_filter( 'request', 'mrh_column_orderby'); 

function mrh_column_orderby( $vars ) {
	if ( !is_admin() )
		return $vars;
	if ( isset( $vars['orderby'] ) && 'cpt_cms_position' == $vars['orderby'] ) {
		$vars = array_merge( $vars, array( 'meta_key'=>'cpt_cms_position', 'orderby'=>'meta_value' ) );
	}
	//elseif ( isset( $vars['orderby'] ) && '') 
	return $vars;
}

add_action( 'restrict_manage_posts', 'mrh_add_taxonomy_filters' );

function mrh_add_taxonomy_filters() {
	global $typenow;
 
	// an array of all the taxonomyies you want to display. Use the taxonomy name or slug
	$taxonomies = array('cms_block_page');
 
	// must set this to the post type you want the filter(s) displayed on
	if( $typenow == 'cms_cpt' ){
 
		foreach ($taxonomies as $tax_slug) {
			$tax_obj = get_taxonomy($tax_slug);
			$tax_name = $tax_obj->labels->name;
			$terms = get_terms($tax_slug);
			if(count($terms) > 0) {
				echo "<select name='$tax_slug' id='$tax_slug' class='postform'>";
				echo "<option value=''>Show All $tax_name</option>";
				foreach ($terms as $term) { 
					echo '<option value='. $term->slug, $_GET[$tax_slug] == $term->slug ? ' selected="selected"' : '','>' . $term->name .' (' . $term->count .')</option>'; 
				}
				echo "</select>";
			}
		}
	}
}

function mrh_cms_shortcode($attrs) {
	shortcode_atts(array(
            'slug' => 'undefined',
            'value' => 'undefined'
        ), $attrs);

	 if ('undefined' === $attrs['slug'] || 'undefined' === $attrs['value']) {
            return NULL;
        } else {
            $cms_block = get_posts(array('post_type' => 'cms_cpt', 'order' => 'ASC', 'posts_per_page' => wp_count_posts('cms_cpt')->publish));
            $posts = array();
            
            foreach ($cms_block as $post_key => $post_item) {
                $posts[$post_item->post_name] = $post_item;
            } //End foreach $cms_blocks
            
            if ('thumbnail' == $attrs['value']) {
                $thumbnail = wp_get_attachment_image_src( get_post_thumbnail_id( $posts[$attrs['slug']]->ID ), 'single-post-thumbnail' );
                return $thumbnail[0];
            } else {
                $post = get_object_vars($posts[$attrs['slug']]);
                return do_shortcode($post[$attrs['value']]);
            }
        }
}

add_shortcode('cms_block', 'mrh_cms_shortcode');


