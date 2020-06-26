<?php
/**
 *@since 2019.07.31
 *自动加载类文件
 *
 * @see 本插件：
 * new Wnd\Model\Wnd_Auth;
 * 对应文件路径
 * includes/model/wnd-auth.php
 *
 * 第三方组件：
 * new Wnd\Component\Aliyun\Sms\SignatureHelper;
 * includes/component/Aliyun/Sms/SignatureHelper.php
 * (注意：第三方组件文件及文件目录需要区分大小写)
 *
 *@see 基于被插件的主题拓展类
 *类名: Wndt\Module\Wndt_Bid_Form
 *路径: {TEMPLATEPATH}/includes/module/wndt-bid-form.php
 *
 *集成的第三方组件，按通用驼峰命名规则
 *请注意文件及文件夹大小写必须一一对应
 * new Wndt\Component\AjaxComment;
 * {TEMPLATEPATH}/includes/component/AjaxComment.php
 *
 * (注意：第三方组件文件及文件目录需要区分大小写)
 *
 * @see 基于被插件的其他插件拓展类
 * new WndPlugin\Wndt_Demo\Wndt_Demo
 * /wp-content/plugins/wndt-demo/wndt-demo.php
 *
 *	component文件夹存储第三方组件，按通用驼峰命名规则
 * 	new WndPlugin\Wndt_Demo\Component\AjaxComment;
 * 	/wp-content/plugins/wndt-demo/component/AjaxComment.php
 * 	(注意：第三方组件文件及文件目录需要区分大小写)
 */
spl_autoload_register(function ($class) {

	// 仅针对Wnd类
	if (0 !== stripos($class, 'wnd')) {
		return;
	}

	/**
	 *解析class
	 *要判断第三方插件 component 目录，至少需要切割四组元素：WndPlugin\Wndt_Demo\Component\AjaxComment;
	 */
	$class_info = explode('\\', $class, 4);
	$domain     = strtolower($class_info[0]);

	/**
	 *根据命名空间前缀定义类文件基本路径
	 *@since 2020.06.25
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

	// 其他插件拓展类：设定插件目录，从数组中剔除固定命名空间前缀 WndPlugin，以匹配后续算法
	case 'wndplugin':
		$base_dir = WP_PLUGIN_DIR . DIRECTORY_SEPARATOR . $class_info[1];
		array_shift($class_info);
		break;

	default:
		return;
		break;
	}

	/**
	 *根据数组元素，判断是否存在子目录
	 *判断子目录是否为 component
	 *
	 * component 目录存放第三方组件，保持大小写。其余目录统一转为小写
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
