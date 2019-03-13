<?php

/**
 *@since 2019.03.13
 */
class Wnd_Posts_Table {

	protected $columns = array();
	public $html;
	public $preview_link;
	public $edit_link;
	static $defaults = array(
		'post_field' => '',
		'title' => '',
		'content' => '',
		'attr' => '',
	);

	function __construct($wp_query_args, $preview_link = false, $edit_link = false) {
		$this->args = $wp_query_args;
		$this->preview_link = $preview_link;
		$this->edit_link = $edit_link;
	}

	function add_column($column = array()) {
		$column = array_merge(Wnd_Posts_Table::$defaults, $column);
		array_push($this->columns, $column);
	}

	function get_attr($column) {
		if ($column['attr']) {
			return ' ' . $column['attr'];
		}
		return '';
	}	

	function build() {

		$wp_query = new WP_Query($this->args);

		// 表单开始
		$this->html = '<table class="table is-fullwidth is-hoverable is-striped">';

		// 表头
		$this->html .= '<thead>';
		$this->html .= '<tr>';
		foreach ($this->columns as $column) {
			$this->html .= '<th' . $this->get_attr($column) . '>' . $column['title'] . '</th>';
		}unset($column);
		if ($this->edit_link or $this->preview_link) {
			$this->html .= '<td class="is-narrow is-hidden-mobile">';
			$this->html .= '操作';
			$this->html .= '</td>';
		}
		$this->html .= '</tr>';
		$this->html .= '</thead>';

		// 列表
		$this->html .= '<tbody>';
		while ($wp_query->have_posts()): $wp_query->the_post();global $post;
			$this->html .= '<tr>';
			foreach ($this->columns as $column) {
				if ($column['post_field'] == 'post_title_with_link') {
					$content = '<a href="' . get_permalink() . '" target="_blank">' . get_the_title() . '</a>';
				} else {
					$content = $column['post_field'] ? get_post_field($column['post_field']) : $column['content'];
				}
				$this->html .= '<td' . $this->get_attr($column) . '>' . $content . '</td>';
			}unset($column);

			if ($this->edit_link or $this->preview_link) {
				$this->html .= '<td class="is-narrow is-hidden-mobile">';

				$this->html .= $this->preview_link ? '<a onclick="wnd_ajax_modal(\'post_info\',\'post_id=' . get_the_ID() . '&amp;color=primary\')"> <i class="fas fa-info-circle"></i> </a>' : '';

				$this->html .= $this->edit_link ? '<a onclick="wnd_ajax_modal(\'post_status_form\',\'' . get_the_ID() . '\')"> <i class="fas fa-edit"></i> </a>' : '';

				$this->html .= '</td>';
			}

			$this->html .= '</tr>';
		endwhile;
		wp_reset_postdata();
		$this->html .= '</tbody>';

		// 表单结束
		$this->html .= '</table>';
	}

}