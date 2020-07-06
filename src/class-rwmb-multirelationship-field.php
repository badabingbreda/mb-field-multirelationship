<?php

add_action( 'init' , 'register_multirelationship_field' );

add_action( 'wp_ajax_get_mrs_options' , 'mb_multirelationship_get_options' );

/**
 * Callback for AJAX request, gets rows and pagination for the select2 dropdown
 *
 * @return [type] [description]
 */
function mb_multirelationship_get_options() {

	$to = filter_input( INPUT_GET, 'to' , FILTER_SANITIZE_STRING );
	$page = filter_input( INPUT_GET, 'page' , FILTER_SANITIZE_NUMBER_INT );
	$q = filter_input( INPUT_GET, 'q' , FILTER_SANITIZE_STRING );

	$args = [ 'post_type' => $to , 'post__not_in' => isset( $_GET['ids']) ? $_GET['ids'] : [] , 'posts_per_page' => 10 , 'paged' => $page , 's' => $q , 'orderby' => 'title' , 'order' => 'ASC' ];

	$results = new WP_Query( $args );

	$row = array();

	foreach ( $results->posts as $post ) {

			$row[] = array( 'id' => $post->ID , 'text' => $post->post_title );

	}
	echo json_encode( array( 'results' => $row , 'pagination' => array( 'more' => ( $results->max_num_pages > $page ) ) ) );

	wp_die();
}

/**
 * Callback to register the field when all necessary classes are found to be present
 *
 * @return [type] [description]
 */
function register_multirelationship_field() {

	if ( !class_exists( 'RWMB_Field' ) || !class_exists( 'RWMB_Select_Advanced_Field' )  || !class_exists( 'MB_Relationships_API' ) ) return;

	class RWMB_Multirelationship_Field extends RWMB_Field {


		/**
		 * Enqueue scripts, localize the parts for the field so we can template it and some styles
		 *
		 * @return [type] [description]
		 */
		public static function admin_enqueue_scripts() {

			// register and enqueue the needed select2 library that comes with Meta Box
			RWMB_Select_Advanced_Field::admin_enqueue_scripts();

			wp_enqueue_script( 'mb-multirelationship', MULTIRELATIONSHIP_URL . 'js/mb-multirelationship.js', array( 'jquery' ), MULTIRELATIONSHIP_VERSION, true );
			wp_enqueue_style( 'mb-multirelationship', MULTIRELATIONSHIP_URL . 'css/mb-field-multirelationship.css', null, MULTIRELATIONSHIP_VERSION, 'all' );
			$templates = array(
					'newRelation' => file_get_contents( MULTIRELATIONSHIP_DIR . 'template/newRelation.html' ),
					'listItem' => file_get_contents( MULTIRELATIONSHIP_DIR . 'template/listItem.html' ) ,
					'adminUrl' => admin_url( 'admin-ajax.php' ),
			);

			wp_localize_script( 'mb-multirelationship', 'mbmrs', $templates );

		}

		/**
		 * HTML Output for the field
		 *
		 * @param  [type] $meta  [description]
		 * @param  [type] $field [description]
		 * @return [type]        [description]
		 */
		public static function html( $meta, $field ) {

				$prefix = '__mrs';
				$hidecss = "";
				$connected = [];
				$is_present = [];


                $default_options = array(
                    'label'             =>  '',         // 'currency' / 'regex' / 'custom'
                    'button_label'		=>	'Add relationship',
                    'to'				=>	'student',
                    'default'			=> [],
                    'autoselect'		=> null,
               		'options'			=> [],
               		'hide_metaboxes'	=> true,
                );

                // parse the field settings
                $field = wp_parse_args(
                    $field,
                    $default_options
                );

			$data = file_get_contents( MULTIRELATIONSHIP_DIR . 'template/field.html' );

			$postid = filter_input( INPUT_GET , 'post' , FILTER_SANITIZE_NUMBER_INT );

			if ( $postid ) {


				foreach( $field[ 'options' ] as $relationship ) {

					$connected = new WP_Query( array(
					    'relationship' => array(
					        'id'   => $relationship['relationship_api_id'],
					        'to' => $postid,
					    ),
					    'post_type' => $field['to'],
					    'nopaging'     => true,
					) );

					if ( !$connected->have_posts() ) continue;

					foreach ($connected->posts as $present ) {

						$is_present[] = array( 'id' => $present->ID , 'label' => $present->post_title , 'value' => $relationship[ 'slug' ] );

					}

				}

			}

			if ($field[ 'hide_metaboxes' ] ) {

				$hidecss = "<style>";
				foreach( $field[ 'options' ] as $relationship )	{
					$hidecss .= "#{$relationship['relationship_api_id']}_relationships_from, #{$relationship['relationship_api_id']}_relationships_to { display: none; }";
				}
				$hidecss .= "</style>";
			}

			// replace the template parts
			$data = str_replace(
									array(
										$prefix . '__id__',
										$prefix . '__autoselect__',
										$prefix . '__to__',
										$prefix . '__button_label__',
										$prefix . '__current_value__',
										$prefix . '__options__',
										$prefix . '__debug__',
										$prefix . '__hidecss__',
									 ),
									array(
										$field[ 'id' ],
										( $field[ 'autoselect' ] !== null ) ? $field[ 'autoselect' ] : '',
										$field[ 'to' ],
										esc_html__( $field['button_label'] ),
										( $is_present && $postid  ) ? json_encode( $is_present ) : json_encode( $field[ 'default' ] ) ,
										json_encode( $field[ 'options' ] ),
										json_encode( $is_present ),
										$hidecss,
									),

									 $data );




			return $data;
		}

		/**
		 * Save the value, but only after doing some freaky things with the relationships too
		 *
		 * @param  [type] $new     [description]
		 * @param  [type] $old     [description]
		 * @param  [type] $post_id [description]
		 * @param  [type] $field   [description]
		 * @return [type]          [description]
		 */
		public static function save( $new , $old , $post_id , $field ) {
		// public static function new_save( $null , $field , $new , $old , $post_id  ) {

			// check the $old and $new value to see if we need to completely remove relationships;
			// when an ID is gone, remove all three relationships

			// str_replace
			$_new = str_replace( array( '\"' ), array( '"' ), $new );
			$_old = str_replace( array( '\"' ), array( '"' ), $old );

			$_new = json_decode( $_new , true );	// decoded items
			$_old = json_decode( $_old , true ); // decoded items

			// make sure we have an array, even if it's empty
			$_new = ( $_new !== null ) ? $_new : [];
			$_old = ( $_old !== null ) ? $_old : [];

			$new_ids = array_map( function ( $item ) { return $item['id']; } , $_new );
			$old_ids = array_map( function ( $item ) { return $item['id']; } , $_old );

			if( $old_ids ) {

				// get the list of ids that need to be complete removed, remove all relations
				$remove_ids = array_diff( $old_ids , $new_ids );

				// remove relations from posts we removed completely
				foreach( $remove_ids as $relation_id ) {
					foreach ( $field[ 'options' ] as $option ) {
						// delete the relationship from current id to the postid we got from the removeids
						MB_Relationships_API::delete( $relation_id , $post_id, $option[ 'relationship_api_id' ] );

					}
				}

			}

			$updates = self::return_update_actions( $_new , $_old , $field );

			//rwmb_set_meta( $post_id , 'lesson_notes', json_encode( $updates ) );

			foreach( $updates as $slug => $update ) {

				if( !isset( $update[ 'items' ] ) ) continue;

				$relationship_api_id = $updates[$slug][ 'relationship_api_id' ];

				foreach ( $update[ 'items' ] as $key => $action ) {

					if ( $action == 'add' ) {
						 MB_Relationships_API::add( $key , $post_id , $relationship_api_id );
					} else {
						MB_Relationships_API::delete( $key , $post_id , $relationship_api_id );

					}
				}
			}

			RWMB_Field::save( $new, $old, $post_id, $field );

		}

		/**
		 * Generate an array that tells us what actions to perform on the intersecting values
		 * @param  [type] $new [description]
		 * @param  [type] $old [description]
		 * @return [type]      [description]
		 */
		public static function return_update_actions( $__new_data , $__old_data , $field ) {

			$options = self::return_api_id_for_slug($field);

			$new_as_id = [];
			$old_as_id = [];
			$updates = [];

			foreach ( $options as $key => $api ) {
				$updates[ $key ][ 'relationship_api_id' ] = $api;
			}

			foreach( $__new_data as $item ) {
				$new_as_id[ $item[ 'id' ] ] = $item;
			}
			foreach( $__old_data as $item ) {
				$old_as_id[ $item[ 'id' ] ] = $item;
			}

			// create another array in which we will register if an id needs to be updated
			foreach($new_as_id as $id => $item ) {

				// use the id to get the old value
				// if there is no old value we just need to add it to the slug, right?
				if (! isset( $old_as_id[ $id ] ) ) {
					$updates[ $item[ 'value' ] ][ 'items' ][ $id ] = 'add' ;
					continue;
				}

				if ( $old_as_id[ $id ][ 'value' ] == $item['value'] ) {
					// do nothing
					continue;
				}
				if ( $old_as_id[ $id ][ 'value' ] !== $item['value'] ) {

					// remove the relationship from the old slug
					$updates[ $old_as_id[ $id ][ 'value' ] ][ 'items' ][ $id ] = 'delete';

					$updates[ $item[ 'value' ] ][ 'items' ][ $id ] = 'add';
					continue;
				}

			}

			return $updates;

		}

		/**
		 * remap an array of slugs so we know which ID belongs to it
		 * @param  [type] $field [description]
		 * @return [type]        [description]
		 */
		public static function return_api_id_for_slug( $field ) {

			$return_option = [];

			foreach( $field['options'] as $item ) {
				$return_option[ $item['slug'] ] = $item[ 'relationship_api_id' ];
			}

			return $return_option;
		}

	}


}
