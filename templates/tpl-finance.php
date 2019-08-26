<?php
// Exit if accessed directly
if (!defined('ABSPATH')) {
	exit;
}

/**
 *@since 2019.02.18 封装用户财务中心
 *@param $posts_per_page 每页列表数目
 */
function _wnd_user_fin_panel(int $posts_per_page = 0) {
	if (!is_user_logged_in()) {
		return;
	}
	$user_id = get_current_user_id();
	$posts_per_page = $posts_per_page ?: get_option('posts_per_page');

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

	if (wnd_get_option('wnd', 'wnd_commission_rate')) {
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
			<button class="button" onclick="wnd_ajax_modal(\'_wnd_recharge_form\')">余额充值</button>
		</div>';

	if (is_super_admin()) {
		$html .= '
		<div class="level-item">
			<button class="button" onclick="wnd_ajax_modal(\'_wnd_admin_recharge_form\')">管理员充值</button>
		</div>';
	}
	$html .= '</div>';

	$filter = new Wnd_Filter(true);
	$filter->add_post_type_filter(array('order', 'recharge'));
	$filter->add_post_status_filter(array('any'));
	$filter->add_query(array('author' => get_current_user_id()));
	$filter->set_posts_template('_wnd_user_fin_posts_tpl');
	$filter->set_posts_per_page($posts_per_page);
	$filter->set_ajax_container('#admin-fin-panel');
	$filter->query();
	$filter_html = $filter->get_tabs() . '<div id="admin-fin-panel">' . $filter->get_results() . '</div>';

	return $html . $filter_html;
}

/**
 *@since 2019.03.14
 *以表格形式输出用户充值及消费记录
 */
function _wnd_user_fin_posts_tpl($query) {
	$table = new Wnd_Posts_Table($query, true, true);
	$table->add_column(
		array(
			'post_field' => 'post_date',
			'title' => '日期',
			'class' => 'is-narrow',
		)
	);
	$table->add_column(
		array(
			'post_field' => 'post_content',
			'title' => '金额',
			'class' => 'is-narrow',
		)
	);
	$table->add_column(
		array(
			'post_field' => 'order' == $query->query_vars['post_type'] ? 'post_parent_with_link' : 'post_title',
			'title' => '详情',
		)
	);
	$table->add_column(
		array(
			'post_field' => 'post_status',
			'title' => '状态',
			'class' => 'is-narrow',
		)
	);
	$table->build();
	return $table->html;
}

/**
 *@since 2019.03.14 财务统计中心
 *@param $posts_per_page 每页列表数目
 */
function _wnd_admin_fin_panel(int $posts_per_page = 0) {
	if (!is_super_admin()) {
		return;
	}
	$posts_per_page = $posts_per_page ?: get_option('posts_per_page');

	$filter = new Wnd_Filter(true);
	$filter->add_post_type_filter(array('stats-ex', 'stats-re', 'order', 'recharge'));
	$filter->add_post_status_filter(array('已完成' => 'success', '进行中' => 'pending'));
	$filter->set_posts_template('_wnd_fin_stats_posts_tpl');
	$filter->set_posts_per_page($posts_per_page);
	$filter->set_ajax_container('#admin-fin-panel');
	$filter->query();
	return $filter->get_tabs() . '<div id="admin-fin-panel">' . $filter->get_results() . '</div>';
}

/**
 *@since 2019.03.14
 *以表格形式输出按月统计
 */
function _wnd_fin_stats_posts_tpl($query) {
	$table = new Wnd_Posts_Table($query, true, true);
	$table->add_column(
		array(
			'post_field' => 'post_date',
			'title' => '日期',
			'class' => 'is-narrow',
		)
	);
	$table->add_column(
		array(
			'post_field' => 'post_author',
			'title' => '用户',
			'class' => 'is-narrow',
		)
	);
	$table->add_column(
		array(
			'post_field' => 'post_content',
			'title' => '金额',
		)
	);
	$table->add_column(
		array(
			'post_field' => 'post_title_with_link',
			'title' => '详情',
			'class' => 'is-hidden-mobile',
		)
	);
	$table->add_column(
		array(
			'post_field' => 'post_status',
			'title' => '状态',
		)
	);
	$table->build();
	return $table->html;
}

/**
 *@since 2019.07.16
 *创建订单链接
 *@param int $post_id 产品/文章ID
 */
function _wnd_order_link($post_id) {
	return wnd_get_do_url() . '?action=payment&post_id=' . $post_id . '&_wpnonce=' . wnd_create_nonce('payment');
}

/**
 *@since 2019.01.21 充值表单
 */
function _wnd_recharge_form() {
	if (!wnd_get_option('wnd', 'wnd_alipay_appid')) {
		return '未设置支付接口';
	}

	$form = new Wnd_Form;
	$form->add_html('<div class="has-text-centered">');
	$form->add_radio(
		array(
			'name' => 'total_amount',
			'options' => array('0.01' => '0.01', '10' => '10', '100' => '100', '200' => '200', '500' => '500'),
			'required' => 'required',
			'checked' => '0.01', //default checked value
			'class' => 'is-checkradio is-danger',
		)
	);
	$form->add_html('<img src="https://t.alipayobjects.com/images/T1HHFgXXVeXXXXXXXX.png">');
	$form->add_html('</div>');
	$form->set_action(wnd_get_do_url(), 'GET');
	$form->add_hidden('_wpnonce', wnd_create_nonce('payment'));
	$form->add_hidden('action', 'payment');
	$form->set_submit_button('充值', 'is-' . wnd_get_option('wnd', 'wnd_primary_color'));
	$form->build();

	return $form->html;
}

/**
 *@since 2019.02.22
 *管理员手动增加用户余额
 */
function _wnd_admin_recharge_form() {
	$form = new Wnd_WP_Form();
	$form->set_form_attr('id="admin-recharge-form"');

	$form->add_html('<div class="field is-horizontal"><div class="field-body">');
	$form->add_text(
		array(
			'label' => '用户<span class="required">*</span>',
			'name' => 'user_field',
			'required' => 'required',
			'placeholder' => '用户名、邮箱、注册手机',
		)
	);
	$form->add_text(
		array(
			'label' => '金额<span class="required">*</span>',
			'name' => 'total_amount',
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
