# 前端Form name规则

## 前端表单遵循以下规则定义的name，后台获取后自动提取，并更新到数据库
	文章：_post_{field}

	文章字段：
	_meta_{key} (*自定义数组字段)
	_wpmeta_{key} (*WordPress原生字段)
	_term_{taxonomy}(*taxonomy)

	用户：_user_{field}

	用户字段：
	_usermeta_{key} (*自定义数组字段)
	_wpusermeta_{key} (*WordPress原生字段)

## Option Form 规则
option表单将整个表单字段作为数组存储在一个特定 wp option 记录中，表单字段 name 规则如下：
```php
"_option_$option_name_$option_key"
```

## 手机验证表单：
	phone
	auth_code

# ajax表单响应代码表
```JavaScript
// 根据后端响应处理 form_id 为当前表单ID
var style = response.status > 0 ? "is-success" : "is-danger";
switch (response.status) {
	// 常规类，展示后端提示信息
	case 1:
		wnd_ajax_msg(response.msg, style, "#" + form_id);
		break;
		//更新类
	case 2:
		wnd_ajax_msg('提交成功！<a href="' + response.data.url + '" target="_blank">查看</a>', style, "#" + form_id);
		break;
		// 跳转类
	case 3:
		wnd_ajax_msg("请稍后……", style, "#" + form_id);
		$(window.location).prop("href", response.data.redirect_to);
		break;
		// 刷新当前页面
	case 4:
		wnd_reset_modal();
		window.location.reload(true);
		break;
		// 弹出信息
	case 5:
		wnd_alert_msg(response.msg);
		break;
		// 下载类
	case 6:
		wnd_ajax_msg("下载中……", style, "#" + form_id, 5);
		$(window.location).prop("href", response.data.redirect_to);
		break;
		//默认展示提示信息
	default:
		wnd_ajax_msg(response.msg, style, "#" + form_id);
		break;
}
```