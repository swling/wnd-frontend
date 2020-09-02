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
success: function(response) {
	if (response.status != 3 && response.status != 4) {
		submit_button.removeClass("is-loading");
	}
	if (response.status != 0 && response.status != 8) {
		submit_button.prop("disabled", true);
	}
	// var submit_text = (response.status > 0) ? wnd.msg.submit_successfully : wnd.msg.submit_failed;
	// submit_button.text(submit_text);
	var style = response.status > 0 ? "is-success" : "is-danger";
	// 根据后端响应处理
	switch (response.status) {
		// 常规类，展示后端提示信息，状态 8 表示禁用提交按钮 
		case 1:
		case 8:
			wnd_ajax_form_msg(form_id, response.msg, style);
			break;
			//更新类
		case 2:
			var msg = wnd.msg.submit_successfully + '<a href="' + response.data.url + '" target="_blank">&nbsp;' + wnd.msg.view + '</a>';
			wnd_ajax_form_msg(form_id, msg, style);
			break;
			// 跳转类
		case 3:
			wnd_ajax_form_msg(form_id, wnd.msg.waiting, style);
			$(window.location).prop("href", response.data.redirect_to);
			break;
			// 刷新当前页面
		case 4:
			wnd_reset_modal();
			window.location.reload(true);
			break;
			// 弹出信息并自动消失
		case 5:
			wnd_alert_msg(response.msg, 1);
			break;
			// 下载类
		case 6:
			wnd_ajax_form_msg(form_id, wnd.msg.downloading, style);
			$(window.location).prop("href", response.data.redirect_to);
			break;
			// 以响应数据替换当前表单
		case 7:
			$("#" + form_id).replaceWith(response.data);
			break;
			// 默认
		default:
			wnd_ajax_form_msg(form_id, response.msg, style);
			break;
	}
}
```