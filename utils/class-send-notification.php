<?php
/**
 * Send_Notification class for sending notifications on post publish.
 *
 * @package Pushrocket
 */

namespace Pushrocket;

/**
 * Send_Notification class for sending notifications on post publish.
 */
class Send_Notification {
	/**
	 * Constructor for the Send_Notification class.
	 */
	public function __construct() {
		add_action( 'plugins_loaded', array( $this, 'init' ), 11 );

	}
	/**
	 * Init action for push notification.
	 */
	public function init() {
		// add_action( 'wp_after_insert_post', array( $this, 'send_notification_on_publish_without_yoast' ), 10, 4 );
		if ( class_exists( 'WPSEO_Options' ) ) {
			add_action( 'wpseo_saved_postdata', array( $this, 'send_notification_on_publish_with_yoast' ), 99 );
		} else {
			add_action( 'wp_after_insert_post', array( $this, 'send_notification_on_publish_without_yoast' ), 10, 4 );
		}
		add_action( 'wp_ajax_push_rocket_send_notification',array( $this, 'push_rocket_send_notification' ));
	}
	/**
	 * Sends a notification when a post or web story is published with yoast.
	 */
	public function send_notification_on_publish_with_yoast() {
		$id    = isset( $_POST['post_ID'] ) ? sanitize_text_field( wp_unslash( $_POST['post_ID'] ) ) : '';
		$post = get_post( ( $id ) );
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		$show_multiple_site = get_transient( 'pushrocket_show_multiple_site' );
		if ( 'yes' !== $show_multiple_site ) {
			return;
		}
		$post_type = $post->post_type;
		if ( 'publish' !== $post->post_status || wp_is_post_revision( $id ) ) {
			return;
		}
		if ( 'post' === $post_type || 'web-story' === $post_type ) {
			$ts1          = strtotime( $post->post_date );
			$ts2          = strtotime( $post->post_modified );
			$seconds_diff = $ts2 - $ts1;
			if ( $seconds_diff < 5 ) {
				$this->send_push_notification( $id );
			}
		} else {
			return false;
		}
	}
	/**
	 * Sends a notification when a post or web story is published without yoast.
	 *
	 * @param int     $id          The ID of the post being published.
	 * @param WP_Post $post        The post object being published.
	 * @param bool    $update      Whether this is an update to an existing post.
	 * @param WP_Post $post_before The post object before the update.
	 */
	public function send_notification_on_publish_without_yoast( $id, $post, $update, $post_before ) {

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		$show_multiple_site = get_transient( 'pushrocket_show_multiple_site' );
		if ( 'yes' !== $show_multiple_site ) {
			return;
		}
		$post_type = $post->post_type;
		if ( 'publish' !== $post->post_status || ( $post_before && 'publish' === $post_before->post_status ) || wp_is_post_revision( $id ) ) {
			return;
		}
		if ( 'post' === $post_type || 'web-story' === $post_type ) {
			if ( $post->post_date === $post->post_modified ) {
				$this->send_push_notification( $id );
			}
		} else {
			return false;
		}
	}
	/**
	 * Sends a notification when a post or web story is published.
	 *
	 * @param int $id The ID of the post being published.
	 */
	public function send_push_notification( $id ) {
		$post                             = get_post( ( $id ) );
		$post_type                        = $post->post_type;
		$pushrocket_panel_url             = get_option( 'pushrocket_panel_url' );
				$pushrocket_website_code  = get_option( 'pushrocket_website_code' );
				$pushrocket_username      = get_option( 'pushrocket_username' );
				$pushrocket_password      = get_option( 'pushrocket_password' );
				$pushrocket_website_lists = get_option( 'pushrocket_website_lists' );
				$website_lists            = count( $pushrocket_website_lists ) > 0 ? implode( ',', $pushrocket_website_lists ) : '';
		if ( 'post' === $post_type ) {
			$pushrocket_push_on_publish = get_option( 'pushrocket_push_on_publish' );
			if ( 1 !== (int) $pushrocket_push_on_publish ) {
				return false;
			}
		}
		if ( 'web-story' === $post_type ) {
			$pushrocket_push_on_publish_for_webstories = get_option( 'pushrocket_push_on_publish_for_webstories' );
			if ( 1 !== (int) $pushrocket_push_on_publish_for_webstories ) {
				return false;
			}
		}
				$title       = '';
				$description = '';
		if ( class_exists( 'WPSEO_Options' ) ) {
			$title       = get_post_meta( $id, '_yoast_wpseo_title', true );
			$description = get_post_meta( $id, '_yoast_wpseo_metadesc', true );
		} elseif ( class_exists( 'RankMath' ) ) {
			$title       = get_post_meta( $id, 'rank_math_title', true );
			$description = get_post_meta( $id, 'rank_math_description', true );
		}
		if ( 'web-story' === $post_type ) {
			$title       = get_the_title( $id );
			$description = get_post_field( 'post_excerpt', $id );
		}
		if ( empty( $title ) ) {
			$title = $post->post_title;
		}
		if ( empty( $description ) ) {
			$description = $post->post_content;
			$description = apply_filters( 'the_content', $post->post_content );
		}
		$img_url      = get_the_post_thumbnail_url( $id, 'large' );
		$data_to_send = array(
			'WebsiteURL'      => $pushrocket_panel_url,
			'WebsiteCode'     => $pushrocket_website_code,
			'UserName'        => $pushrocket_username,
			'Password'        => $pushrocket_password,
			'WebsiteList'     => $website_lists,
			'MetaTitle'       => $title,
			'MetaDescription' => $description,
			'ImageURL'        => $img_url,
			'PostURL'         => get_the_permalink( $id ),
			'PostType'        => $post_type,
		);
		$response     = wp_remote_post(
			'https://pushrocket.one/api/User/SendNotification',
			array(
				'body' => $data_to_send, // Pass the data here.
			)
		);
	}
	/**
     * Send Notification on Row Action Click
     *
     * @since 1.0.0
     */
    public function push_rocket_send_notification()
    {
        $id = sanitize_text_field($_POST['post_id']);
        $this->send_push_notification( $id );
    }
}

new Send_Notification();

