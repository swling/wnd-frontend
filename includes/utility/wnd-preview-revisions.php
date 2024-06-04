<?php
namespace Wnd\Utility;

/**
 * Class to add revisions preview functionality.
 * @link https://github.com/rtCamp/wordpress-preview-revisions
 *
 * @package preview-revisions
 */

/**
 * Preview_Revisions class.
 */
class Wnd_Preview_Revisions {

	/**
	 * Construct method.
	 */
	public function __construct() {
		$this->setup_hooks();
	}

	/**
	 * Function to setup hooks.
	 *
	 * @return void
	 */
	public function setup_hooks(): void {
		// Filters.
		add_filter('posts_request', [$this, 'modify_posts_request']);
		add_filter('posts_results', [$this, 'inherit_parent_status']);
		add_filter('the_posts', [$this, 'undo_inherit_parent_status']);
	}

	/**
	 * Function to modify the post request.
	 *
	 * @param string $posts_request Posts Request.
	 *
	 * @return string $posts_request Modified Posts Request.
	 */
	public function modify_posts_request(string $posts_request): string {
		if (is_admin()) {
			return $posts_request;
		}

		if (!isset($_GET['p']) || empty($_GET['p'])) {
			return $posts_request;
		}

		$revision_id = (int) filter_input(INPUT_GET, 'p', FILTER_VALIDATE_INT);
		$revision    = wp_get_post_revision($revision_id);
		if (!$revision || 'revision' !== $revision->post_type) {
			return $posts_request;
		}

		$pub_post = get_post($revision->post_parent);
		if (!$pub_post) {
			return $posts_request;
		}

		$type_obj = get_post_type_object($pub_post->post_type);
		if (!$type_obj) {
			return $posts_request;
		}

		if (!current_user_can('read_post', $revision_id) || !current_user_can('edit_post', $revision_id)) {
			return $posts_request;
		}

		$posts_request = str_replace("post_type = 'post'", "post_type = 'revision'", $posts_request);
		return str_replace("post_type = '{$pub_post->post_type}'", "post_type = 'revision'", $posts_request);

	}

	/**
	 * Add posts_results post status to work the functionality.
	 *
	 * @param array $posts_results Posts Results.
	 *
	 * @return array $posts_results Modified Posts Results.
	 */
	public function inherit_parent_status(array $posts_results): array {
		global $wp_post_statuses;

		$wp_post_statuses['inherit']->protected = true;
		return $posts_results;

	}

	/**
	 * Undo the post status.
	 *
	 * @param array $posts Posts Results.
	 *
	 * @return array
	 */
	public function undo_inherit_parent_status(array $posts): array {
		global $wp_post_statuses;

		$wp_post_statuses['inherit']->protected = false;
		return $posts;
	}
}
