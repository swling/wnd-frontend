<?php
namespace Wnd\Action;

use Exception;

/**
 *@since 0.9.25
 *响应前端富文本编辑器文件上传
 *
 *响应数据格式应该根据具体采用的编辑器确定，本插件目前采用国产开源编辑器 wangEditor
 *@link https://doc.wangeditor.com
 *@return $return_array array 数组
 *	[
 *		'errno' => 0,
 *		'data' => [
 *			['url' => $url, 'alt' => '', 'href' => ''],
 *		]
 *	],
 */
class Wnd_Upload_File_Editor extends Wnd_Action {
	/**
	 *本操作非标准表单请求，无需验证签名
	 */
	protected $verify_sign = false;

	public function execute(): array{

		$post_parent = (int) ($this->data['post_parent'] ?? 0);
		$save_width  = (int) ($this->data['save_width'] ?? 0);
		$save_height = (int) ($this->data['save_height'] ?? 0);

		// These files need to be included as dependencies when on the front end.
		if (!is_admin()) {
			require ABSPATH . 'wp-admin/includes/image.php';
			require ABSPATH . 'wp-admin/includes/file.php';
			require ABSPATH . 'wp-admin/includes/media.php';
		}

		/**
		 *@since 2019.05.06 改写
		 *遍历文件上传
		 */
		$return_array = [
			'errno' => 0,
			'data'  => [],
		];
		$files = $_FILES['wnd_file']; //暂存原始上传信息，后续将重写$_FILES全局变量以适配WordPress上传方式

		foreach ($files['name'] as $key => $value) {
			// 将多文件上传数据遍历循环后，重写为适配 media_handle_upload 的单文件模式
			$file = [
				'name'     => $files['name'][$key],
				'type'     => $files['type'][$key],
				'tmp_name' => $files['tmp_name'][$key],
				'error'    => $files['error'][$key],
				'size'     => $files['size'][$key],
			];
			$_FILES = ['temp_key' => $file];

			// 单文件错误检测
			if ($_FILES['temp_key']['error'] > 0) {
				$return_array[] = ['status' => 0, 'msg' => 'Error: ' . $_FILES['temp_key']['error']];
				continue;
			}

			//上传文件并附属到对应的post parent 默认为0 即孤立文件
			$file_id = media_handle_upload('temp_key', $post_parent);

			// 上传失败
			if (is_wp_error($file_id)) {
				$return_array[] = ['status' => 0, 'msg' => $file_id->get_error_message()];
				continue;
			}

			$url = wp_get_attachment_url($file_id);

			// 判断是否为图片
			if (strrpos($file['type'], 'image') !== false) {
				//根据尺寸进行图片裁剪
				if ($save_width or $save_height) {
					//获取文件服务器路径
					$image_file = get_attached_file($file_id);
					$image      = wp_get_image_editor($image_file);
					if (!is_wp_error($image)) {
						$image->resize($save_width, $save_height, ['center', 'center']);
						$image->save($image_file);
					}
				}
			}

			// 写入响应数组
			$return_array['data'][] = [
				'url' => $url,
			];
		}
		unset($key, $value);

		// 返回上传信息
		return $return_array;
	}

	/**
	 *权限检测
	 */
	protected function check() {
		// 继承父类权限
		parent::check();

		//$_FILES['wnd_file']需要与input name 值匹配
		if (empty($_FILES['wnd_file'])) {
			throw new Exception(__('上传文件为空', 'wnd'));
		}

		$post_parent = (int) ($this->data['post_parent'] ?? 0);

		// 上传信息校验
		if (!$this->user_id and !$post_parent) {
			throw new Exception(__('User ID及Post ID不可同时为空', 'wnd'));
		}

		/**
		 *@since 2019.05.08 上传文件meta_key post_parent校验
		 *meta_key 及 post_parent同时为空时，上传文件将成为孤立的的文件，在前端上传附件应该具有明确的用途，应避免这种情况
		 */
		if (!$post_parent) {
			throw new Exception(__('Meta_key与Post_parent不可同时为空', 'wnd'));
		}

		if ($post_parent and !get_post($post_parent)) {
			throw new Exception(__('post_parent无效', 'wnd'));
		}
	}
}
