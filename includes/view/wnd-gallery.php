<?php
namespace Wnd\View;

/**
 *@since 0.9.2
 *
 *相册图集
 */
class Wnd_Gallery {

	/**
	 *将外部传参与默认参数合并，以确保参数完整
	 */
	public static function parse_args(array $args): array{
		$defaults = [
			'id'             => uniqid(),
			'label'          => 'Gallery',
			'thumbnail_size' => ['width' => 200, 'height' => 150],
			'data'           => [],
			'ajax'           => true,
			'method'         => 'POST',
			'input_name'     => 'wnd_file',
		];
		$args = array_merge($defaults, $args);

		/**
		 * - 合并$data
		 * - 相册的meta key为固定值，不接受参数修改
		 */
		$args['data']['meta_key'] = 'gallery';
		$defaults_data            = [
			'post_parent' => 0,
			'user_id'     => 0,
			'meta_key'    => 'gallery',
			'save_width'  => 0, //图片文件存储最大宽度 0 为不限制
			'save_height' => 0, //图片文件存储最大过度 0 为不限制
		];
		$args['data'] = array_merge($defaults_data, $args['data']);

		return $args;
	}

	/**
	 *构建相册上传
	 *@param array 传参 详情参看 static::parse_args();
	 *@param bool 是否对外部传参与默认参数合并解析（当外部传参不完整时，需选择此项，否则将出现参数未定义等错误）
	 *@return string html element
	 */
	public static function build_gallery_upload(array $args, $parse_args = true): string{
		$args = $parse_args ? static::parse_args($args) : $args;

		/**
		 *@since 2019.12.13
		 *
		 *将$args['data']数组拓展为变量
		 *
		 *$post_parent
		 *$user_id
		 *$meta_key
		 *……
		 */
		extract($args['data']);

		// 固定data
		$args['data']['upload_nonce']     = wp_create_nonce('wnd_upload_file');
		$args['data']['delete_nonce']     = wp_create_nonce('wnd_delete_file');
		$args['data']['meta_key_nonce']   = wp_create_nonce($meta_key);
		$args['data']['thumbnail_width']  = $args['thumbnail_size']['width'];
		$args['data']['thumbnail_height'] = $args['thumbnail_size']['height'];
		$args['data']['method']           = $args['ajax'] ? 'ajax' : $args['method'];

		// 根据user type 查找目标文件
		$images = $post_parent ? wnd_get_post_meta($post_parent, $meta_key) : wnd_get_user_meta($user_id, $meta_key);
		$images = is_array($images) ? $images : [];

		/**
		 *@since 2019.05.06 构建 html
		 */
		$id   = 'gallery-' . $args['id'];
		$data = ' data-id="' . $id . '"';
		foreach ($args['data'] as $key => $value) {
			$data .= ' data-' . $key . '="' . $value . '" ';
		}unset($key, $value);

		$html = '<div id="' . $id . '" class="field upload-field">';
		$html .= '<div class="field"><div class="ajax-message"></div></div>';

		// 上传区域
		$html .= '<div class="field">';
		$html .= '<div class="file">';
		$html .= '<label class="file-label">';
		$html .= '<input type="file" multiple="multiple" class="file-input" name="' . $args['input_name'] . '[]' . '"' . $data . 'accept="image/*" >';
		$html .= ' <span class="file-cta"><span class="file-icon"><i class="fas fa-upload"></i></span><span class="file-label">选择图片</span></span>';
		$html .= '</label>';
		$html .= '</div>';
		$html .= '</div>';

		// 遍历输出图片集
		$html .= '<div class="gallery columns is-vcentered has-text-centered is-multiline is-marginless is-mobile">';
		if (!$images) {
			$html .= '<div class="column default-message">';
			$html .= '<p>' . $args['label'] . '</p>';
			$html .= '</div>';
		}
		foreach ($images as $key => $attachment_id) {
			$attachment_url = wp_get_attachment_url($attachment_id);
			$thumbnail_url  = wnd_get_thumbnail_url($attachment_url, $args['thumbnail_size']['width'], $args['thumbnail_size']['height']);
			if (!$attachment_url) {
				unset($images[$key]); // 在字段数据中取消已经被删除的图片
				continue;
			}

			$html .= '<div class="attachment-' . $attachment_id . ' column is-narrow">';
			$html .= '<a><img class="thumbnail" src="' . $thumbnail_url . '" data-url="' . $attachment_url . '" height="' . $args['thumbnail_size']['height'] . '" width="' . $args['thumbnail_size']['width'] . '"></a>';
			$html .= '<a class="delete" data-id="' . $id . '" data-file_id="' . $attachment_id . '"></a>';
			$html .= '</div>';
		}
		unset($key, $attachment_id);

		// 若字段中存在被删除的图片数据，此处更新
		if ($post_parent) {
			wnd_update_post_meta($post_parent, $meta_key, $images);
		} else {
			wnd_update_user_meta($user_id, $meta_key, $images);
		}

		$html .= '</div>';
		$html .= '</div>';

		return $html;
	}

	/**
	 *@since 2019.05.05
	 *Post gallery 相册展示
	 *@param $post_id 			int 		相册所附属的文章ID
	 *@param $thumbnail_width 	number 		缩略图宽度
	 *@param $thumbnail_height 	number 		缩略图高度
	 **/
	public static function build_post_gallery(int $post_id, int $thumbnail_width, int $thumbnail_height): string{
		$images = wnd_get_post_meta($post_id, 'gallery');
		if (!$images) {
			return false;
		}

		$data = static::build_gallery($images, $thumbnail_width, $thumbnail_height);

		// 若字段中存在被删除的图片数据，此处更新
		wnd_update_post_meta($post_id, 'gallery', $data['images']);

		return $data['html'];
	}

	/**
	 *@since 2020.07.15
	 *User gallery 相册展示
	 *@param $user_id 			int 		相册所附属的用户ID
	 *@param $thumbnail_width 	number 		缩略图宽度
	 *@param $thumbnail_height 	number 		缩略图高度
	 **/
	public static function build_user_gallery(int $user_id, int $thumbnail_width, int $thumbnail_height): string{
		$images = wnd_get_user_meta($user_id, 'gallery');
		if (!$images) {
			return false;
		}

		$data = static::build_gallery($images, $thumbnail_width, $thumbnail_height);

		// 若字段中存在被删除的图片数据，此处更新
		wnd_update_user_meta($user_id, 'gallery', $data['images']);

		return $data['html'];
	}

	/**
	 *@since 0.9.2
	 *根据附件id构造相册，并对检测id有效性
	 *返回构造完成的相册 html ，及剔除无效附件id的附件id数据
	 */
	protected static function build_gallery(array $images, int $thumbnail_width, int $thumbnail_height): array{
		// 遍历输出图片集
		$html = '<div class="gallery columns is-vcentered is-multiline has-text-centered is-marginless is-mobile">';
		foreach ($images as $key => $attachment_id) {
			$attachment_url = wp_get_attachment_url($attachment_id);
			$thumbnail_url  = wnd_get_thumbnail_url($attachment_url, $thumbnail_width, $thumbnail_height);
			if (!$attachment_url) {
				unset($images[$key]); // 在字段数据中取消已经被删除的图片
				continue;
			}

			$html .= '<div id="attachment-' . $attachment_id . '" class="column is-narrow">';
			$html .= '<a><img class="thumbnail" src="' . $thumbnail_url . '" data-url="' . $attachment_url . '" height="' . $thumbnail_height . '" width="' . $thumbnail_width . '"></a>';
			$html .= '</div>';
		}
		unset($key, $attachment_id);
		$html .= '</div>';

		return ['html' => $html, 'images' => $images];
	}
}
