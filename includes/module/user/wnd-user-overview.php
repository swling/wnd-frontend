<?php
namespace Wnd\Module\User;

use Wnd\Module\Wnd_Module_Html;

/**
 * 插件默认账户概览
 * @since 0.9.2
 */
class Wnd_User_Overview extends Wnd_Module_Html {

	protected static function build(array $args = []): string{
		$user_id = get_current_user_id();
		$html    = '';

		// 账户概览
		$html .= '<div class="is-divider" data-content="账户概览"></div>';
		$html .= static::build_financial_overview($user_id);

		// 退出按钮
		$html .= '<div class="has-text-centered is-size-3">';
		$html .= '<a href="' . wp_logout_url(home_url()) . '" title="退出"><i class="fas fa-power-off"></i></a>';
		$html .= '</div>';

		return $html;
	}

	/**
	 * 财务概览
	 */
	public static function build_financial_overview($user_id) {
		$user_id = get_current_user_id();

		// 用户余额
		$html = '<div class="level is-mobile has-text-centered">';
		$html .= '<div class="level-item">';
		$html .= '<div>';
		$html .= '<p class="heading">余额</p>';
		$html .= '<p>' . wnd_get_user_balance($user_id, true) . '</p>';
		$html .= '</div>';
		$html .= '</div>';

		$html .= '<div class="level-item">';
		$html .= '<div>';
		$html .= '<p class="heading">消费</p>';
		$html .= '<p>' . wnd_get_user_expense($user_id, true) . '</p>';
		$html .= '</div>';
		$html .= '</div>';

		$html .= '<div class="level-item">';
		$html .= '<div>';
		$html .= '<p class="heading">资源</p>';
		$html .= '<p>0篇</p>';
		$html .= '</div>';
		$html .= '</div>';
		$html .= '</div>';

		$html .= '<div class="level is-mobile">';
		$html .= '<div class="level-item">';
		$html .= wnd_modal_button('余额充值', 'common/wnd_user_recharge_form', [], 'is-outlined');
		$html .= '</div>';

		if (is_super_admin()) {
			$html .= '<div class="level-item">';
			$html .= wnd_modal_button('人工充值', 'admin/wnd_admin_recharge_form', [], 'is-outlined');
			$html .= '</div>';
		}
		$html .= '</div>';

		return $html;
	}
}
