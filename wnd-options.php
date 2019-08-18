<?php
// Exit if accessed directly
if (!defined('ABSPATH')) exit;

/**
 *@since 2019.01.16 注册设置页面
 */
function wnd_menu()
{
	add_options_page('Wnd Frontend Setting', 'Wnd Frontend', 'administrator', 'wndwp_options', 'wnd_options');
}
add_action('admin_menu', 'wnd_menu');


/*设置选项*/
function wnd_options()
{
	if ($_POST && current_user_can('administrator')) {
		check_admin_referer('wnd_update');
		$option_array = array();
		foreach ($_POST as $key => $value) {
			// 按前缀筛选数组,过滤掉非指定数据
			if (strpos($key, 'wnd_') === false) {
				unset($_POST[$key]);
			} else {
				// 替换空格和中文逗号
				$value = str_replace(' ', '', $value);
				$value = str_replace('，', ',', $value);
				$option_array = array_merge($option_array, array($key => $value));
			}
		}
		unset($key, $value);

		// 更新设置
		update_option('wnd', $option_array);
		echo '<div class="updated settings-error"><p>更新成功！</p></div>';
	}
	?>
<div class="wrap">
	<h1>万能的WordPress前端框架设置</h1>
	<form method="post" action="">
		<table class="form-table">
			<!--前端表单设置-->
			<tr>
				<th valign="top">
					安全校验
				</th>
				<td>
					<p><i>作用：表单提交数据时，是否校验表单字段名（防止用户通过浏览器开发模式篡改表单结构）</i></p>
				</td>
			</tr>
			<tr>
				<td valign="top">校验秘钥</td>
				<td>
					<input type="text" name="wnd_secret_key" value="<?php echo wnd_get_option('wnd', 'wnd_secret_key'); ?>" class="regular-text">
					<p><i>前端操作权限校验秘钥，请设置一个较为复杂的秘钥，避免在流量高峰期修改</i></p>
				</td>
			</tr>
			<tr>
				<td valign="top">表单校验</td>
				<td>
					开启校验<input type="radio" required="required" name="wnd_enable_form_verify" value="1" <?php if (wnd_get_option('wnd', 'wnd_enable_form_verify') == 1) echo 'checked' ?> />
					关闭校验<input type="radio" required="required" name="wnd_enable_form_verify" value="0" <?php if (wnd_get_option('wnd', 'wnd_enable_form_verify') != 1) echo 'checked' ?> />
					<p><i>作用：表单提交数据时，是否校验表单字段名（防止用户通过浏览器开发模式篡改表单结构）<br />
							警告：仅在开发测试中关闭过滤，否则可能引发安全问题</i></p>
				</td>
			</tr>

			<!--优化选项-->
			<tr>
				<th valign="top">
					优化选项
				</th>
			</tr>

			<tr>
				<td valign="top">默认样式</td>
				<td>
					开启<input type="radio" required="required" name="wnd_enable_default_style" value="1" <?php if (wnd_get_option('wnd', 'wnd_enable_default_style') == 1) echo 'checked' ?> />
					关闭<input type="radio" required="required" name="wnd_enable_default_style" value="0" <?php if (wnd_get_option('wnd', 'wnd_enable_default_style') != 1) echo 'checked' ?> />
					<p><i>是否启用默认样式，默认采用bulma css框架font-awesome图标，关闭后需要自行设置前端效果</i></p>
				</td>
			</tr>

			<tr>
				<td valign="top">编辑页面</td>
				<td>
					<?php wp_dropdown_pages('show_option_none=—选择—&name=wnd_edit_page&selected=' . wnd_get_option('wnd', 'wnd_edit_page')); ?>
					<p><i>前端编辑页面（设置后将覆盖WordPress前端编辑链接）</i></p>
				</td>
			</tr>

			<tr>
				<td valign="top">注册协议</td>
				<td>
					<input type="text" name="wnd_agreement_url" value="<?php echo wnd_get_option('wnd', 'wnd_agreement_url'); ?>" class="large-text">
					<p><i>新用户注册协议页面</i></p>
				</td>
			</tr>

			<tr>
				<td valign="top">注册跳转</td>
				<td>
					<input type="text" name="wnd_reg_redirect_url" value="<?php echo wnd_get_option('wnd', 'wnd_reg_redirect_url'); ?>" class="large-text">
					<p><i>新用户注册后跳转地址</i></p>
				</td>
			</tr>

			<tr>
				<td valign="top">默认头像</td>
				<td>
					<input type="text" name="wnd_default_avatar_url" value="<?php echo wnd_get_option('wnd', 'wnd_default_avatar_url'); ?>" class="large-text">
					<p><i>默认用户头像地址</i></p>
				</td>
			</tr>

			<tr>
				<td valign="top">前端最大上传（KB）</td>
				<td>
					<input type="number" name="wnd_max_upload_size" value="<?php echo wnd_get_option('wnd', 'wnd_max_upload_size'); ?>" class="text" min="1">
					<p><i>前端文件最大上传限制（默认2048KB，不得大于服务器设置）</i></p>
				</td>
			</tr>

			<tr>
				<td valign="top">最大置顶文章数量</td>
				<td>
					<input type="number" name="wnd_max_stick_posts" value="<?php echo wnd_get_option('wnd', 'wnd_max_stick_posts'); ?>" class="text" min="1">
					<p><i>限制置顶文章数量，按新旧顺序保留（非WordPress原生置顶功能）</i></p>
				</td>
			</tr>

			<tr>
				<td valign="top">前台禁用语言包</td>
				<td>
					禁用语言包<input type="radio" required="required" name="wnd_disable_locale" value="1" <?php if (wnd_get_option('wnd', 'wnd_disable_locale') == 1) echo 'checked'; ?> />
					启用语言包<input type="radio" required="required" name="wnd_disable_locale" value="0" <?php if (wnd_get_option('wnd', 'wnd_disable_locale') != 1) echo 'checked'; ?> />
					<p><i>前端禁用语言包，有效节省内存和生成时间，但某些情况下可能会出现英文信息，请先行测试</i></p>
				</td>
			</tr>

			<tr>
				<td valign="top">优化用户字段</td>
				<td>
					开启优化<input type="radio" required="required" name="wnd_unset_user_meta" value="1" <?php if (wnd_get_option('wnd', 'wnd_unset_user_meta') == 1) echo 'checked'; ?> />
					禁用优化<input type="radio" required="required" name="wnd_unset_user_meta" value="0" <?php if (wnd_get_option('wnd', 'wnd_unset_user_meta') != 1) echo 'checked'; ?> />
					<p><i>注册用户不需要登录到WordPress后台时可开启</i></p>
				</td>
			</tr>

			<tr>
				<td valign="top">禁止WP后台</td>
				<td>
					禁止<input type="radio" required="required" name="wnd_disable_admin_panel" value="1" <?php if (wnd_get_option('wnd', 'wnd_disable_admin_panel') == 1) echo 'checked'; ?> />
					允许<input type="radio" required="required" name="wnd_disable_admin_panel" value="0" <?php if (wnd_get_option('wnd', 'wnd_disable_admin_panel') != 1) echo 'checked'; ?> />
					<p><i>是否禁止普通用户访问WordPress管理后台</i></p>
				</td>
			</tr>

			<!--优化选项-->
			<tr>
				<th valign="top">
					色调配置
				</th>
				<td>
					<p><i>用于配置表单按钮等颜色，参考bulma框架，若需更详细的颜色控制，请在主题CSS中设置颜色覆盖</i></p>
				</td>
			</tr>

			<tr>
				<td valign="top">主色调</td>
				<td>
					<?php echo _wnd_dropdown_colors('wnd_primary_color', wnd_get_option('wnd', 'wnd_primary_color')); ?>
				</td>
			</tr>

			<tr>
				<td valign="top">辅色调</td>
				<td>
					<?php echo _wnd_dropdown_colors('wnd_second_color', wnd_get_option('wnd', 'wnd_second_color')); ?>
				</td>
			</tr>

			<!-- 佣金设置 -->
			<tr>
				<th valign="top">
					作者佣金设置
				</th>
				<td>
					<input type="number" name="wnd_commission_rate" value="<?php echo wnd_get_option('wnd', 'wnd_commission_rate'); ?>" class="text" max="1" min="0" step="0.01">
					<p><i>当用户发布的付费内容产生消费时，作者获得的佣金比例（0.00 ~ 1.00）</i></p>
				</td>
			</tr>

			<!--支付设置-->
			<tr>
				<th valign="top">
					支付宝设置
				</th>
				<td><i>加签方式：RSA(SHA256)密钥</i> <a href="https://openclub.alipay.com/read.php?tid=2217&fid=69" target="_blank"><i>支付宝帮助文档</i></a></td>
			</tr>
			<tr>
				<td valign="top">充值后返回</td>
				<td>
					<input type="text" name="wnd_pay_return_url" value="<?php echo wnd_get_option('wnd', 'wnd_pay_return_url'); ?>" class="large-text">
					<p><i>用户充值后跳转地址</i></p>
				</td>
			</tr>

			<tr>
				<td valign="top">支付宝APP ID</td>
				<td>
					<input type="text" name="wnd_alipay_appid" value="<?php echo wnd_get_option('wnd', 'wnd_alipay_appid'); ?>" class="regular-text">
				</td>
			</tr>

			<tr>
				<td valign="top">支付宝私钥</td>
				<td>
					<textarea class="code" name="wnd_alipay_private_key" cols="40" rows="8" style="min-width: 50%;" placeholder="开发者私钥，由开发者自己生成"><?php echo wnd_get_option('wnd', 'wnd_alipay_private_key'); ?></textarea>
				</td>
			</tr>

			<tr>
				<td valign="top">支付宝公钥</td>
				<td>
					<textarea class="code" name="wnd_alipay_public_key" cols="40" rows="8" style="min-width: 50%;" placeholder="支付宝公钥，开发者生成公钥后上传至支付宝，再由支付宝生成"><?php echo wnd_get_option('wnd', 'wnd_alipay_public_key'); ?></textarea>
				</td>
			</tr>

			<!--term设置-->
			<tr>
				<th valign="top">
					分类关联标签
				</th>
			</tr>
			<tr>
				<td valign="top">是否启用</td>
				<td>
					开启<input type="radio" name="wnd_enable_terms" value="1" <?php if (wnd_get_option('wnd', 'wnd_enable_terms') == 1) echo 'checked'; ?>>
					关闭<input type="radio" name="wnd_enable_terms" value="0" <?php if (wnd_get_option('wnd', 'wnd_enable_terms') != 1) echo 'checked'; ?>>
					<p><i>是否开启分类关联标签功能</i></p>
				</td>
			</tr>

			<!--短信设置-->
			<tr>
				<th valign="top">
					短信配置
				</th>
			</tr>
			<tr>
				<td valign="top">启用短信功能</td>
				<td>
					开启<input type="radio" name="wnd_enable_sms" value="1" <?php if (wnd_get_option('wnd', 'wnd_enable_sms') == 1) echo 'checked'; ?>>
					关闭<input type="radio" name="wnd_enable_sms" value="0" <?php if (wnd_get_option('wnd', 'wnd_enable_sms') != 1) echo 'checked'; ?>>
					<p><i>是否开启短信验证功能</i></p>
				</td>
			</tr>
			<tr>
				<td valign="top">邮箱注册</td>
				<td>
					关闭<input type="radio" name="wnd_disable_email_reg" value="1" <?php if (wnd_get_option('wnd', 'wnd_disable_email_reg') == 1) echo 'checked'; ?> />
					开启<input type="radio" name="wnd_disable_email_reg" value="0" <?php if (wnd_get_option('wnd', 'wnd_disable_email_reg') != 1) echo 'checked'; ?> />
					<p><i>关闭邮箱注册则强制手机注册。请确保手机验证可用，否则用户无法注册！</i></p>
				</td>
			</tr>
			<tr>
				<td valign="top">腾讯云短信APP ID</td>
				<td>
					<input type="text" name="wnd_sms_appid" value="<?php echo wnd_get_option('wnd', 'wnd_sms_appid'); ?>" class="regular-text">
				</td>
			</tr>
			<tr>
				<td valign="top">腾讯云短信APP Key</td>
				<td>
					<input type="text" name="wnd_sms_appkey" value="<?php echo wnd_get_option('wnd', 'wnd_sms_appkey'); ?>" class="regular-text" />
				</td>
			</tr>
			<tr>
				<td valign="top">腾讯云短信签名</td>
				<td>
					<input type="text" name="wnd_sms_sign" value="<?php echo wnd_get_option('wnd', 'wnd_sms_sign'); ?>" class="regular-text">
					*请注意：签名需要先在腾讯云后台提交审核
				</td>
			</tr>
			<tr>
				<td valign="top">默认短信模板</td>
				<td>
					<input type="text" name="wnd_sms_template" value="<?php echo wnd_get_option('wnd', 'wnd_sms_template'); ?>" class="regular-text">
					*默认短信模板，短信表单未指定短信模板时，最后调用本模板
				</td>
			</tr>
			<tr>
				<td valign="top">注册短信模板</td>
				<td>
					<input type="text" name="wnd_sms_template_r" value="<?php echo wnd_get_option('wnd', 'wnd_sms_template_r'); ?>" class="regular-text">
					注册时的短信模板代码（可选）
				</td>
			</tr>
			<tr>
				<td valign="top">信息变更模板</td>
				<td>
					<input type="text" name="wnd_sms_template_v" value="<?php echo wnd_get_option('wnd', 'wnd_sms_template_v'); ?>" class="regular-text">
					信息变更验证码代码（如修改密码等，可选）
				</td>
			</tr>

		</table>
		<?php wp_nonce_field('wnd_update'); ?>
		<input type="submit" value="保存设置" class="button-primary">
	</form>
	<p><a href="https://wndwp.com">插件教程</a></p>
</div>
<?php
}
