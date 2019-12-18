<?php
namespace Wnd\Module;

use Wnd\Model\Wnd_Tag_Under_Category;

/**
 * Term模块
 */
class Wnd_Term_Tpl {

	/**
	 *获取指定taxonomy的分类列表并附带下属标签
	 *@since 2018
	 */
	public static function list_categories_with_tags($cat_taxonomy, $tag_taxonomy = 'any', $limit = 10, $show_count = false, $hide_empty = 1) {
		$args  = ['hide_empty' => $hide_empty, 'orderby' => 'count', 'order' => 'DESC'];
		$terms = get_terms($cat_taxonomy, $args);
		if (empty($terms) or is_wp_error($terms)) {
			return;
		}

		$html = '<div class="list-' . $cat_taxonomy . '-with-tags list-categories-with-tags">' . PHP_EOL;
		foreach ($terms as $term) {
			// 获取分类
			$html .= '<div id="category-' . $term->term_id . '" class="category-with-tags">' . PHP_EOL;
			$html .= '<h3><a href="' . get_term_link($term) . '">' . $term->name . '</a></h3>' . PHP_EOL;
			$tag_list = '<ul class="list-tags-under-' . $term->term_id . ' list-tags-under-category">' . PHP_EOL;
			$tags     = Wnd_Tag_Under_Category::get_tags($term->term_id, $tag_taxonomy, $limit);
			foreach ($tags as $tag) {
				$tag_id       = (int) $tag->tag_id;
				$tag_taxonomy = $tag->tag_taxonomy;
				$tag          = get_term($tag_id);
				if (!$tag or is_wp_error($tag)) {
					Wnd_Tag_Under_Category::delete_term($tag_id, $tag_taxonomy);
					continue;
				}

				//输出常规链接
				if ($show_count) {
					$tag_list .= '<li><a href="' . get_term_link($tag_id) . '" >' . $tag->name . '</a>（' . $tag->count . '）</li>' . PHP_EOL;
				} else {
					$tag_list .= '<li><a href="' . get_term_link($tag_id) . '" >' . $tag->name . '</a></li>' . PHP_EOL;
				}
			}
			unset($tag);

			$tag_list .= '</ul>';
			$html .= $tag_list;
			$html .= '</div>' . PHP_EOL;
		}
		unset($term);
		$html .= '</div>' . PHP_EOL;

		return $html;
	}

	/**
	 *@since 2019.04.25（未完成）
	 *下拉菜单形式，生成taxonomy 多重查询参数 GET key：_term_{$taxonomy} GET value: $term_id
	 *@param $args 						array 		get_terms参数
	 *@param $remove_query_arg 			array 		需要移除的参数
	 *@param $title 					string 		标题label
	 */
	// public static function term_select_query_arg() {}

	/**
	 *
	 *以文本方式列出热门标签，分类名称 用于标签编辑器，自动提示文字： 'tag1', 'tag2', 'tag3'
	 *
	 */
	public static function terms_text($taxonomy, $number) {
		$terms = get_terms($taxonomy, 'orderby=count&order=DESC&hide_empty=0&number=' . $number);
		if (!empty($terms)) {
			if (!is_wp_error($terms)) {
				$terms_list = '';
				foreach ($terms as $term) {
					$terms_list .= '\'' . $term->name . '\',';
				}

				// 移除末尾的逗号
				return rtrim($terms_list, ',');
			}
		}
	}

	/**
	 *@since ≈2018.07
	 *表单设置：标签编辑器
	 */
	public static function tags_editor_script($maxTags = 3, $maxLength = 10, $placeholder = '标签', $taxonomy = '') {
		$html = '<script src="https://cdn.jsdelivr.net/npm/jquery-ui-dist@1.12.1/jquery-ui.min.js"></script>';
		$html .= '<script src="' . WND_URL . 'static/js/jquery.tag-editor.min.js"></script>';
		$html .= '<script src="' . WND_URL . 'static/js/jquery.caret.min.js"></script>';
		$html .= '<link rel="stylesheet" href="' . WND_URL . 'static/css/jquery.tag-editor.min.css">';
		$html .=
		'<script>
		jQuery(document).ready(function($) {
			$("#tags").tagEditor({
				//自动提示
				autocomplete: {
					delay: 0,
					position: {
						collision: "flip"
					},
					source: [' . self::terms_text($taxonomy, 50) . ']
				},
				forceLowercase: false,
				placeholder: "' . $placeholder . '",
				maxTags: "' . $maxTags . '", //最多标签个数
				maxLength: "' . $maxLength . '", //单个标签最长字数
				onChange: function(field, editor, tags) {},
			});
		});
		</script>';

		return $html;
	}
}
