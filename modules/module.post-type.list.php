<?php


function wpleads_count_associated_lead_items($post_id){
	global $wpdb;

	$list = get_post($post_id);
	$list_slug = $list->post_name;


	$args = array(
		'post_type' => 'wp-lead',
		'post_status' => 'published',
		'wplead_list_category' => $list_slug,
		'numberposts' => -1
	);

	$num = count( get_posts( $args ) );


	return sprintf( __( '%d leads' , 'leads' ) , $num );
}




function wpleads_add_list_to_wplead_list_category_taxonomy($post_id, $list_title, $list_slug = null)
{

	$wplead_cat_id = get_post_meta( $post_id, 'wplead_list_category_id', true);
	if ($wplead_cat_id)
	{
		wp_update_term( $wplead_cat_id, 'wplead_list_category', array('name'=>$list_title) );
	}
	else
	{
		//add category taxonomy in wpleads
		$term = wp_insert_term( $list_title, 'wplead_list_category', $args = array('slug'=>$list_slug) );

		if (is_object($term))
		{
			$term_error_array = $term->error_data;
			$term_id = $term_error_array['term_exists'];
		}
		else
		{
			$term_id = $term['term_id'];
		}

		update_post_meta( $post_id, 'wplead_list_category_id', $term_id);
	}
}

function wpleads_add_lead_to_list( $list_id, $lead_id )
{

	$wplead_cat_id = get_post_meta($list_id,'wplead_list_category_id', true);

	wp_set_post_terms( $lead_id, intval($wplead_cat_id), 'wplead_list_category', true);

	//build meta pair for list ids lead belongs to
	$wpleads_list_ids = get_post_meta($lead_id, 'wpleads_list_ids', true);

	if ($wpleads_list_ids)
	{
		$wpleads_list_ids_new = array();

		//get array
		$wpleads_list_ids = json_decode($wpleads_list_ids, true);
		if ( !is_array($wpleads_list_ids) )
			$wpleads_list_ids = array();

		//clean
		delete_post_meta($lead_id, 'wpleads_list_ids');

		//restore
		foreach ($wpleads_list_ids as $key=>$value)
		{
			if ($value)
			{
				//echo $value;
				$list = get_post($value['list_id']);
				$list_name = $list->post_name;
				$wplead_cat_id = get_post_meta($value,'wplead_list_category_id', true);

				$wpleads_list_ids_new[$list_name]['list_id'] = $value['list_id'];
				$wpleads_list_ids_new[$list_name]['wplead_list_category_id'] = $wplead_cat_id;
			}
		}

		//push newest if not exists
		if (!in_array($list_id, $wpleads_list_ids_new))
		{
			$list = get_post($list_id);
			$list_name = $list->post_name;

			$wplead_cat_id = get_post_meta($list_id,'wplead_list_category_id', true);

			$wpleads_list_ids_new[$list_name]['list_id'] = $list_id;
			$wpleads_list_ids_new[$list_name]['wplead_list_category_id'] = $wplead_cat_id;
		}


		//print_r($wpleads_list_ids_new);exit;

		$wpleads_list_ids_new = json_encode($wpleads_list_ids_new);
		$wpleads_list_ids_new = update_post_meta($lead_id, 'wpleads_list_ids', $wpleads_list_ids_new);
	}
	else
	{
		// REWRITE TO GET TAX ID
		$list = get_post($list_id);
		$list_name = $list->post_name;
		$wpleads_list_ids[$list_name]['list_id'] = $list_id;
		$wpleads_list_ids[$list_name]['wplead_list_category_id'] = $wplead_cat_id;

		$wpleads_list_ids = json_encode($wpleads_list_ids);
		$wpleads_list_ids = update_post_meta($lead_id , 'wpleads_list_ids', $wpleads_list_ids);
	}

}

function wpleads_remove_lead_from_list( $list_id, $lead_id )
{
	$categories = wp_get_post_terms( $lead_id, 'wplead_list_category', array( 'fields'=>'ids' ) );

	$list_category_id = get_post_meta($list_id,'wplead_list_category_id', true);

	$pos = array_search( $list_category_id, $categories );

	if( false !== $pos ) {
		unset( $categories[$pos] );
	}

	wp_set_post_terms ($lead_id, $categories, 'wplead_list_category');

	//build meta pair for list ids lead belongs to
	$wpleads_list_ids = get_post_meta($lead_id, 'wpleads_list_ids' , true);

	if ($wpleads_list_ids)
	{
		//get array
		$wpleads_list_ids = json_decode($wpleads_list_ids, true);

		if ( !is_array(wpleads_list_ids) )
			$wpleads_list_ids = array();

		//clean
		delete_post_meta($lead_id, 'wpleads_list_ids');

		//rebuild
		foreach ($wpleads_list_ids as $key=>$value)
		{
			if ($value['ID']==$list_id)
				unset($wpleads_list_ids[$key]);
		}

		//store
		$wpleads_list_ids = json_encode($wpleads_list_ids);
		$wpleads_list_ids = update_post_meta($lead_id, 'wpleads_list_ids', $wpleads_list_ids);
	}

}

/* Get Array of Lead Lists from taxonomy */
function wpleads_get_lead_lists_as_array() {

	$args = array(
	    'hide_empty' => false,
	);
	
	$terms = get_terms('wplead_list_category', $args);

	foreach ( $terms as $term  ) {
		$array[$term->term_id] = $term->name;
	}

	return $array;
}


?>