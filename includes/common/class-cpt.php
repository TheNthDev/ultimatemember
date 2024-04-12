<?php
namespace um\common;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'um\common\CPT' ) ) {

	/**
	 * Class CPT
	 *
	 * @package um\common
	 *
	 * @since 2.6.8
	 */
	class CPT {

		public function hooks() {
			add_action( 'init', array( &$this, 'create_post_types' ), 1 );
			add_action( 'wp_trash_post', array( $this, 'change_default_form' ) );
		}

		/**
		 * Create taxonomies for use for UM
		 */
		public function create_post_types() {
			register_post_type(
				'um_form',
				array(
					'labels'       => array(
						'name'               => __( 'Forms', 'ultimate-member' ),
						'singular_name'      => __( 'Form', 'ultimate-member' ),
						'add_new'            => __( 'Add New', 'ultimate-member' ),
						'add_new_item'       => __( 'Add New Form', 'ultimate-member' ),
						'edit_item'          => __( 'Edit Form', 'ultimate-member' ),
						'not_found'          => __( 'You did not create any forms yet', 'ultimate-member' ),
						'not_found_in_trash' => __( 'Nothing found in Trash', 'ultimate-member' ),
						'search_items'       => __( 'Search Forms', 'ultimate-member' ),
					),
					'capabilities' => array(
						'edit_post'          => 'manage_options',
						'read_post'          => 'manage_options',
						'delete_post'        => 'manage_options',
						'edit_posts'         => 'manage_options',
						'edit_others_posts'  => 'manage_options',
						'delete_posts'       => 'manage_options',
						'publish_posts'      => 'manage_options',
						'read_private_posts' => 'manage_options',
					),
					'show_ui'      => true,
					'show_in_menu' => false,
					'public'       => false,
					'show_in_rest' => true,
					'supports'     => array( 'title' ),
				)
			);

			if ( UM()->options()->get( 'members_page' ) ) {
				register_post_type(
					'um_directory',
					array(
						'labels'       => array(
							'name'               => __( 'Member Directories', 'ultimate-member' ),
							'singular_name'      => __( 'Member Directory', 'ultimate-member' ),
							'add_new'            => __( 'Add New', 'ultimate-member' ),
							'add_new_item'       => __( 'Add New Member Directory', 'ultimate-member' ),
							'edit_item'          => __( 'Edit Member Directory', 'ultimate-member' ),
							'not_found'          => __( 'You did not create any member directories yet', 'ultimate-member' ),
							'not_found_in_trash' => __( 'Nothing found in Trash', 'ultimate-member' ),
							'search_items'       => __( 'Search Member Directories', 'ultimate-member' ),
						),
						'capabilities' => array(
							'edit_post'          => 'manage_options',
							'read_post'          => 'manage_options',
							'delete_post'        => 'manage_options',
							'edit_posts'         => 'manage_options',
							'edit_others_posts'  => 'manage_options',
							'delete_posts'       => 'manage_options',
							'publish_posts'      => 'manage_options',
							'read_private_posts' => 'manage_options',
						),
						'show_ui'      => true,
						'show_in_menu' => false,
						'public'       => false,
						'show_in_rest' => true,
						'supports'     => array( 'title' ),
					)
				);
			}
		}

		/**
		 * @since 2.8.0
		 * @return array
		 */
		public function get_list() {
			$cpt_list = array(
				'um_form',
			);
			if ( UM()->options()->get( 'members_page' ) ) {
				$cpt_list[] = 'um_directory';
			}
			/**
			 * Filters registered CPT in Ultimate Member.
			 *
			 * @since 2.0
			 * @hook um_cpt_list
			 *
			 * @param {array} $cpt_list CPT keys.
			 *
			 * @return {array} CPT keys.
			 *
			 * @example <caption>Add `my_cpt` CPT to UM CPT list.</caption>
			 * function um_custom_cpt_list( $cpt_list ) {
			 *     $cpt_list[] = '{my_cpt}';
			 *     return $cpt_list;
			 * }
			 * add_filter( 'um_cpt_list', 'um_custom_cpt_list' );
			 */
			return apply_filters( 'um_cpt_list', $cpt_list );
		}

		/**
		 * @param null|string $post_type
		 *
		 * @since 2.8.0
		 *
		 * @return array
		 */
		public function get_taxonomies_list( $post_type = null ) {
			$taxonomies = apply_filters( 'um_cpt_taxonomies_list', array() );

			if ( isset( $post_type ) ) {
				$taxonomies = array_key_exists( $post_type, $taxonomies ) ? $taxonomies[ $post_type ] : array();
			}
			return $taxonomies;
		}


		public function change_default_form( $form_id ) {
			if ( 'um_form' === get_post_type( $form_id ) ) {
				$core_forms = get_option( 'um_core_forms', array() );
				$mode       = get_post_meta( $form_id, '_um_mode', true );
				if ( isset( $mode ) && absint( $form_id ) === absint( $core_forms[ $mode ] ) ) {
					$args = array(
						'post_type'      => 'um_form',
						'meta_key'       => '_um_mode',
						'meta_value'     => $mode,
						'posts_per_page' => 1,
						'orderby'        => 'date',
						'post_status'    => 'publish',
						'order'          => 'DESC',
						'fields'         => 'ids',
						'post__not_in'   => array( $form_id ),
					);

					$forms = get_posts( $args );
					if ( ! empty( $forms ) ) {
						$new_form_id         = $forms[0];
						$core_forms[ $mode ] = $new_form_id;

						/**
						 * Filters Ultimate Member default forms ids.
						 *
						 * @param {array} $core_forms Default forms ids.
						 * @param {int}   $form_id    Deleted form ID.
						 *
						 * @return {array} Default forms ids.
						 *
						 * @since 2.8.6
						 * @hook um_default_forms_ids
						 *
						 * @example <caption>Set default profile form ID as 1.</caption>
						 * function my_um_default_forms_ids( $core_forms, $form_id ) {
						 *     // your code here
						 *     $core_forms['profile'] = 1;
						 *     return $core_forms;
						 * }
						 * add_filter( 'um_default_forms_ids', 'my_um_default_forms_ids', 10, 2 );
						 */
						$core_forms = apply_filters( 'um_default_forms_ids', $core_forms, $form_id );
						update_option( 'um_core_forms', $core_forms );
					}
				}
			}
		}
	}
}
