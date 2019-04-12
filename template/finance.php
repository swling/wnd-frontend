<?php
// Exit if accessed directly
if (!defined('ABSPATH')) {
	exit;
}

/**
 *@since 2019.02.18 封装用户财务中心
 */
function _wnd_user_fin_panel($args = '') {

	if (!is_user_logged_in()) {
		return;
	}

	// ajax请求类型
	$ajax_type = $_POST['ajax_type'] ?? 'modal';
	$user_id = get_current_user_id();

	// args
	$defaults = array(
		'post_type' => $_GET['type'] ?? 'order',
		'post_status' => 'any',
		'paged' => $_GET['pages'] ?? 1,
		'posts_per_page' => get_option('posts_per_page'),
	);
	$args = wp_parse_args($args, $defaults);

	// active
	$order_is_active = $args['post_type'] == 'order' ? 'class="is-active"' : '';
	$recharge_is_active = $args['post_type'] == 'recharge' ? 'class="is-active"' : '';

	$html = '<div id="user-fin">';

	$html .= '<nav class="level">';
	$html .= '
		<div class="level-item has-text-centered">
			<div>
				<p class="heading">余额</p>
				<p class="title">' . wnd_get_user_money($user_id) . '</p>
			</div>
		</div>';

	$html .= '
		<div class="level-item has-text-centered">
			<div>
				<p class="heading">消费</p>
				<p class="title">' . wnd_get_user_expense($user_id) . '</p>
			</div>
		</div>';

	if (wnd_get_option('wndwp', 'wnd_commission_rate')) {
		$html .= '
		<div class="level-item has-text-centered">
			<div>
				<p class="heading">佣金</p>
				<p class="title">' . wnd_get_user_commission($user_id) . '</p>
			</div>
		</div>';
	}
	$html .= '</nav>';

	$html .= '<div class="level">';
	$html .= '
		<div class="level-item">
			<button class="button" onclick="wnd_ajax_modal(\'recharge_form\')">余额充值</button>
		</div>';

	if (is_super_admin()) {
		$html .= '
		<div class="level-item">
			<button class="button" onclick="wnd_ajax_modal(\'admin_recharge_form\')">管理员充值</button>
		</div>';
	}
	$html .= '</div>';

	$html .= '<div class="tabs">';
	$html .= '<ul>';

	// 配置ajax请求参数
	$ajax_args_order = array_merge($args, array('post_type' => 'order'));
	unset($ajax_args_order['paged']);
	$ajax_args_order = http_build_query($ajax_args_order);

	// 配置ajax请求参数
	$ajax_args_recharge = array_merge($args, array('post_type' => 'recharge'));
	unset($ajax_args_recharge['paged']);
	$ajax_args_recharge = http_build_query($ajax_args_recharge);

	if (wnd_doing_ajax()) {
		if ($ajax_type == 'modal') {
			$html .= '<li ' . $order_is_active . ' ><a onclick="wnd_ajax_modal(\'user_fin_panel\',\'' . $ajax_args_order . '\');">订单记录</a></li>';
			$html .= '<li ' . $recharge_is_active . ' ><a onclick="wnd_ajax_modal(\'user_fin_panel\',\'' . $ajax_args_recharge . '\');">充值记录</a></li>';
		} else {
			$html .= '<li ' . $order_is_active . ' ><a onclick="wnd_ajax_embed(\'#user-fin\',\'user_fin_panel\',\'' . $ajax_args_order . '\');">订单记录</a></li>';
			$html .= '<li ' . $recharge_is_active . ' ><a onclick="wnd_ajax_embed(\'#user-fin\',\'user_fin_panel\',\'' . $ajax_args_recharge . '\');">充值记录</a></li>';
		}
	} else {
		$html .= '<li ' . $order_is_active . ' ><a href="' . add_query_arg('type', 'order', remove_query_arg('pages')) . '">订单记录</a></li>';
		$html .= '<li ' . $recharge_is_active . ' ><a href="' . add_query_arg('type', 'recharge', remove_query_arg('pages')) . '">充值记录</a></li>';
	}

	$html .= '</ul>';
	$html .= '</div>';

	$html .= '<div id="user-fin-list">';
	$html .= _wnd_list_user_fin($args);
	$html .= '</div></div>';

	return $html;

}

/**
 *@since 2019.02.15
 *以表格形式输出WordPress文章列表
 *$pages_key = 'pages', $color = 'is-primary' 仅在非ajax状态下有效
 */
function _wnd_list_user_fin($args = '') {

	$args = wp_parse_args($args);

	// 优先参数
	$args['author'] = get_current_user_id();

	$query = new WP_Query($args);

	if ($query->have_posts()):

		$html = '<table class="table is-fullwidth is-hoverable is-striped">';
		$html .= '<thead>';
		$html .= '<tr>';
		$html .= '<th class="is-narrow is-hidden-mobile"><abbr title="Position">日期</abbr></th>';
		$html .= '<th>金额</th>';
		$html .= '<th>详情</th>';
		$html .= '<th class="is-narrow is-hidden-mobile">状态</th>';
		$html .= '<th class="is-narrow is-hidden-mobile">操作</th>';
		$html .= '</tr>';
		$html .= '</thead>';
		$html .= '<tbody>';

		while ($query->have_posts()): $query->the_post();
			global $post;

			$html .= '<tr>';

			$html .= '<td class="is-narrow is-hidden-mobile">' . get_the_time('m-d H:i') . '</td>';
			$html .= '<td>' . $post->post_content . '</td>';

			if ($post->post_parent) {
				$html .= '<td><a href="' . get_the_permalink($post->post_parent) . '" target="_blank">' . $post->post_title . '</a></td>';
			} else {
				$html .= '<td>' . $post->post_title . '</td>';
			}

			$html .= '<td class="is-narrow is-hidden-mobile">' . $post->post_status . '</td>';

			$html .= '<td class="is-narrow is-hidden-mobile">';
			if (current_user_can('edit_post', $post->ID)) {
				$html .= '<a onclick="wnd_ajax_modal(\'post_status_form\',\'' . $post->ID . '\')"><i class="fas fa-cog"></i></a>';
			}
			$html .= '</td>';

			$html .= '</tr>';
		endwhile;
		wp_reset_postdata(); //重置查询?

		$html .= '</tbody>';
		$html .= '</table>';

		// 分页
		if (!wnd_doing_ajax()) {
			$html .= _wnd_next_page($args['posts_per_page'], $query->post_count, 'pages');
		} else {
			$html .= _wnd_ajax_next_page(__FUNCTION__, $args, $query->post_count);
		}

		// 没有内容
	else :
		$no_more_text = ($args['paged'] >= 2) ? '没有更多内容！' : '没有匹配的内容！';
		$html = '<div class="message is-warning"><div class="message-body">' . $no_more_text . '</div></div>';
	endif;

	return $html;
}

/**
 *@since 2019.01.21 充值表单
 */
function _wnd_recharge_form() {

	if (wnd_get_option('wndwp', 'wnd_alipay_appid')) {

	}

	$form = new Wnd_Form;

	$form->add_html('<div class="has-text-centered">');
	$form->add_Radio(
		array(
			'name' => 'money',
			'value' => array('0.01' => '0.01', '10' => '10'),
			'required' => 'required',
			'checked' => '0.01', //default checked value
			'class' => 'is-checkradio is-danger',
		)
	);
	$form->add_html('<img src="https://t.alipayobjects.com/images/T1HHFgXXVeXXXXXXXX.png">');
	$form->add_html('</div>');

	$form->set_action('post', wnd_get_do_url() . '?action=payment');
	$form->add_hidden('_wpnonce', wp_create_nonce('wnd_payment'));
	$form->set_submit_button('充值');
	$form->build();

	return $form->html;

}

/**
 *@since 2019.02.22
 *管理员手动增加用户余额
 */
function _wnd_admin_recharge_form() {

	$form = new Wnd_Ajax_Form();
	$form->set_form_attr('id="admin-recharge-form"');

	$form->add_html('<div class="field is-horizontal"><div class="field-body">');
	$form->add_text(
		array(
			'label' => '用户<span class="required">*</span>',
			'name' => 'user_field',
			'required' => 'required',
			'placeholder' => '用户名、ID、或邮箱',
		)
	);
	$form->add_text(
		array(
			'label' => '金额<span class="required">*</span>',
			'name' => 'money',
			'required' => 'required',
			'placeholder' => '充值金额（负数可扣款）',
		)
	);
	$form->add_html('</div></div>');

	$form->add_text(
		array(
			'name' => 'remarks',
			'placeholder' => '备注（可选）',
		)
	);

	$form->set_action('wnd_ajax_admin_recharge');
	$form->set_submit_button('确认充值');
	$form->build();

	return $form->html;

}

/**
 *@since 2019.03.14 财务统计中心
 */
function _wnd_admin_fin_panel($args = '') {

	if (!is_super_admin()) {
		return;
	}

	// ajax请求类型
	$ajax_type = $_POST['ajax_type'] ?? 'modal';

	// args
	$defaults = array(
		'post_type' => $_GET['type'] ?? 'stats-ex',
		'post_status' => 'any',
		'paged' => $_GET['pages'] ?? 1,
		'posts_per_page' => get_option('posts_per_page'),
	);
	$args = wp_parse_args($args, $defaults);

	// active
	$recharge_is_active = $args['post_type'] == 'stats-re' ? 'class="is-active"' : '';
	$expense_is_active = $args['post_type'] == 'stats-ex' ? 'class="is-active"' : '';

	$html = '<div id="admin-fin">';
	$html .= '<div class="tabs">';
	$html .= '<ul>';

	// 配置ajax请求参数
	$ajax_args_expense = array_merge($args, array('post_type' => 'stats-ex'));
	unset($ajax_args_expense['paged']);
	$ajax_args_expense = http_build_query($ajax_args_expense);

	// 配置ajax请求参数
	$ajax_args_recharge = array_merge($args, array('post_type' => 'stats-re'));
	unset($ajax_args_recharge['paged']);
	$ajax_args_recharge = http_build_query($ajax_args_recharge);

	if (wnd_doing_ajax()) {
		if ($ajax_type == 'modal') {
			$html .= '<li ' . $expense_is_active . ' ><a onclick="wnd_ajax_modal(\'admin_fin_panel\',\'' . $ajax_args_expense . '\');">消费统计</a></li>';
			$html .= '<li ' . $recharge_is_active . ' ><a onclick="wnd_ajax_modal(\'admin_fin_panel\',\'' . $ajax_args_recharge . '\');">充值统计</a></li>';
		} else {
			$html .= '<li ' . $expense_is_active . ' ><a onclick="wnd_ajax_embed(\'#admin-fin\',\'admin_fin_panel\',\'' . $ajax_args_expense . '\');">消费统计</a></li>';
			$html .= '<li ' . $recharge_is_active . ' ><a onclick="wnd_ajax_embed(\'#admin-fin\',\'admin_fin_panel\',\'' . $ajax_args_recharge . '\');">充值统计</a></li>';
		}
	} else {
		$html .= '<li ' . $expense_is_active . ' ><a href="' . add_query_arg('type', 'stats-ex', remove_query_arg('pages')) . '">消费统计</a></li>';
		$html .= '<li ' . $recharge_is_active . ' ><a href="' . add_query_arg('type', 'stats-re', remove_query_arg('pages')) . '">充值统计</a></li>';
	}
	$html .= '</ul>';
	$html .= '</div>';
	$html .= '<div id="admin-fin-list">';
	$html .= _wnd_list_fin_stats($args);

	$html .= '</div>';
	$html .= '</div>';

	return $html;
}

/**
 *@since 2019.03.14
 *以表格形式输出按月统计
 */
function _wnd_list_fin_stats($args = '') {

	$args = wp_parse_args($args);
	$query = new WP_Query($args);

	if ($query->have_posts()):

		$html = '<table class="table is-fullwidth is-hoverable is-striped">';
		$html .= '<thead>';
		$html .= '<tr>';
		$html .= '<th class="is-narrow is-hidden-mobile"><abbr title="Position">月份</abbr></th>';
		$html .= '<th>金额</th>';
		$html .= '<th>详情</th>';
		$html .= '<th class="is-narrow is-hidden-mobile">操作</th>';
		$html .= '</tr>';
		$html .= '</thead>';

		$html .= '<tbody>';
		while ($query->have_posts()): $query->the_post();
			global $post;
			$html .= '<tr>';
			$html .= '<td class="is-narrow is-hidden-mobile">' . get_the_time('m-d') . '</td>';
			$html .= '<td>' . $post->post_content . '</td>';
			if ($post->post_parent) {
				$html .= '<td><a href="' . get_the_permalink($post->post_parent) . '" target="_blank">' . $post->post_title . '</a></td>';
			} else {
				$html .= '<td>' . $post->post_title . '</td>';
			}
			$html .= '<td class="is-narrow is-hidden-mobile">';
			if (current_user_can('edit_post', $post->ID)) {
				$html .= '<a onclick="wnd_ajax_modal(\'post_status_form\',\'' . $post->ID . '\')"><i class="fas fa-cog"></i></a>';
			}
			$html .= '</td>';
			$html .= '</tr>';
		endwhile;
		wp_reset_postdata(); //重置查询?
		$html .= '</tbody>';

		$html .= '</table>';
		// 分页
		if (!wnd_doing_ajax()) {
			$html .= _wnd_next_page($args['posts_per_page'], $query->post_count, 'pages');
		} else {
			$html .= _wnd_ajax_next_page(__FUNCTION__, $args, $query->post_count);
		}

		// 没有内容
	else :
		$no_more_text = ($args['paged'] >= 2) ? '没有更多内容！' : '没有匹配的内容！';
		$html = '<div class="message is-warning"><div class="message-body">' . $no_more_text . '</div></div>';
	endif;

	return $html;

}