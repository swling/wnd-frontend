<?php
/**
 * # 自动加载类文件
 * - (注意：component 目录中的类与文件名需严格区分大小写)
 *
 * ## 一、本插件：
 * new Wnd\WPDB\Wnd_Auth; 对应文件路径 includes/model/wnd-auth.php
 *
 * ### 第三方组件：
 * - new Wnd\Component\Aliyun\Sms\SignatureHelper;
 * - includes/component/Aliyun/Sms/SignatureHelper.php
 *
 * ## 二、基于本插件的主题拓展类
 * - 类名: Wndt\Module\Wndt_Bid_Form
 * - 路径: {TEMPLATEPATH}/includes/module/wndt-bid-form.php
 *
 * ### 集成的第三方组件，按通用驼峰命名规则
 * - new Wndt\Component\AjaxComment;
 * - {TEMPLATEPATH}/includes/component/AjaxComment.php
 *
 * ## 三、基于被插件的其他插件拓展类
 * - new Wnd_Plugin\Wndt_Demo\Wndt_Demo
 * - /wp-content/plugins/wndt-demo/wndt-demo.php
 *
 * 	### component文件夹存储第三方组件，按通用驼峰命名规则
 * 	- new Wnd_Plugin\Wndt_Demo\Component\AjaxComment;
 * 	- /wp-content/plugins/wndt-demo/component/AjaxComment.php
 *
 * @since 2019.07.31
 */
spl_autoload_register(function ($class) {
	/**
	 * 解析class
	 * 要判断第三方插件 component 目录，至少需要切割四组元素：Wnd_Plugin\Wndt_Demo\Component\AjaxComment;
	 */
	$class_info = explode('\\', $class, 4);
	$domain     = strtolower($class_info[0]);

	/**
	 * 根据命名空间前缀定义类文件基本路径
	 * @since 2020.06.25
	 */
	switch ($domain) {
		// 本插件类
		case 'wnd':
			$base_dir = WND_PATH . DIRECTORY_SEPARATOR . 'includes';
			break;

		// 主题拓展类
		case 'wndt':
			$base_dir = TEMPLATEPATH . DIRECTORY_SEPARATOR . 'includes';
			break;

		// 其他插件拓展类：设定插件目录，从数组中剔除固定命名空间前缀 Wnd_Plugin，以匹配后续算法
		case 'wnd_plugin':
			$base_dir = WP_PLUGIN_DIR . DIRECTORY_SEPARATOR . strtolower($class_info[1]);
			array_shift($class_info);
			break;

		// 都不匹配，表明当前类不是本插件加载范围
		default:
			return;
			break;
	}

	/**
	 * - 判断子目录是否为 component
	 * - component 目录存放第三方组件，保持大小写。其余目录统一转为小写
	 */
	if (isset($class_info[2])) {
		$sub_dir    = strtolower($class_info[1]);
		$class_name = $class_info[2] . (isset($class_info[3]) ? DIRECTORY_SEPARATOR . $class_info[3] : '');
	} else {
		$sub_dir    = '';
		$class_name = $class_info[1];
	}
	$class_name = ('component' == $sub_dir) ? $class_name : strtolower($class_name);

	// 加载文件
	$path = $base_dir . DIRECTORY_SEPARATOR . ($sub_dir ? $sub_dir . DIRECTORY_SEPARATOR : '') . $class_name;
	$path = str_replace('_', '-', $path);
	$path = str_replace('\\', DIRECTORY_SEPARATOR, $path);
	$file = $path . '.php';
	if (file_exists($file)) {
		require $file;
	}
});
