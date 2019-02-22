/*
 *#################### 初始化
 */
var $ = jQuery.noConflict();
// js 获取cookie
function getCookie(c_name) {
	if (document.cookie.length > 0) {
		c_start = document.cookie.indexOf(c_name + "=")
		if (c_start != -1) {
			c_start = c_start + c_name.length + 1
			c_end = document.cookie.indexOf(";", c_start)
			if (c_end == -1) c_end = document.cookie.length
			return unescape(document.cookie.substring(c_start, c_end))
		}
	}
	return false;
}

// js 写入cookie
function setCookie(name, value, seconds, path = "/") {
	seconds = seconds ? (seconds * 1000) : 0; //转化为妙
	if (!seconds) return false;
	var expires = "";
	var date = new Date();
	date.setTime(date.getTime() + seconds);
	expires = "; expires=" + date.toGMTString();
	document.cookie = name + "=" + escape(value) + expires + "; path=" + path; //转码并赋值
}

//判断是否为移动端
function wnd_is_mob() {
	if (navigator.userAgent.match(/(iPhone|iPad|iPod|Android|ios|App\/)/i)) {
		return true;
	} else {
		return false;
	}
}

/**
 *@since 2019.02.14 搜索引擎爬虫
 */
function wnd_is_spider() {
	var userAgent = navigator.userAgent;
	// 蜘蛛判断
	if (userAgent.match(/(Googlebot|Baiduspider|spider)/i)) {
		return true;
	} else {
		return false;
	}
}

//发送按钮倒计时
var wait = 90; // 获取验证码短信时间间隔 按钮不能恢复 可检查号码
var send_countdown;

function wnd_send_countdown() {
	if (wait > 0) {
		$(".send-code").text(wait + "秒");
		wait--;
		send_countdown = setTimeout(wnd_send_countdown, 1000);
	} else {
		$(".send-code").text("获取验证码").attr("disabled", false).fadeTo("slow", 1);
		wait = 90;
	}
}

/**
 *@since 2019.1 bulma重构
 */

//弹出bulma对话框
function wnd_alert_modal(html) {
	wnd_reset_modal();
	$(".modal").addClass("is-active");
	$(".modal-entry").addClass("box");
	$(".modal-entry").html(html);
}

// 直接弹出消息
var ajax_alert_time_out;

function wnd_alert_msg(msg, wait = 0) {

	wnd_reset_modal();
	$(".modal").addClass("is-active");
	$(".modal-entry").removeClass("box");
	$(".modal-entry").html('<div class="alert-msg" style="color:#FFF;text-align:center">' + msg + '</div>');
	// 定时关闭
	if (wait > 0) {
		ajax_alert_time_out = setTimeout(function() {
			wnd_reset_modal()
		}, wait * 1000);
	}

}

// 在表单提交时反馈信息
var ajax_msg_time_out;

function wnd_ajax_msg(msg, color = "is-danger", parent = "body", wait = 0) {
	$(parent + " .ajax-msg:first").html('<div class="message ' + color + '"><div class="message-body">' + msg + '</div></div>');
	// 非对话框，返回表单顶部以展示提示信息
	if (!$(".modal").hasClass("is-active")) {
		var target = $(parent).get(0);
		target.scrollIntoView({
			behavior: "smooth"
		});
	}
	// 定时清空
	if (wait > 0) {
		ajax_msg_time_out = setTimeout(function() {
			$(parent + " .ajax-msg:first").empty();
		}, wait * 1000);
	}
}

// 初始化对话框
function wnd_reset_modal() {
	// 清空定时器
	clearTimeout(ajax_msg_time_out);
	clearTimeout(ajax_alert_time_out);
	clearTimeout(send_countdown);

	if ($("#modal").length) {
		$(".modal").removeClass("is-active");
		$(".ajax-msg").empty();
		$(".modal-entry").empty();
	} else {
		$("body").append(
			'<div id="modal" class="modal">' +
			'<div class="modal-background"></div>' +
			'<div class="modal-content">' +
			'<div class="modal-entry"></div>' +
			'</div>' +
			'<button class="modal-close is-large" aria-label="close"></button>' +
			'</div>'
		);
	}
}

// 关闭对话框
jQuery(document).ready(function($) {
	$("body").off("click").on("click", ".modal-background,.modal-close", function() {
		wnd_reset_modal();
	});

});

// 点击触发，点击A 元素 触发 B元素点击事件 用于部分UI优化操作
function wnd_click(target_element) {
	$(target_element).trigger("click");
}

// 表单提交确认对话框  注意：提交表单的触发按钮，不应该是submit or button 而应该单独定义如 input 等，绑定本函数
function wnd_confirm_form_submit(form_id, msg = '') {
	wnd_alert_modal(
		'<p>' + msg + '</p>' +
		'<div class="field is-grouped is-grouped-centered">' +
		'<div class="control">' +
		'<button class="button is-dark"  onclick="wnd_ajax_submit(\'' + form_id + '\')" >确定</button>' +
		'</div>' +
		'</div>'
	);
}

/**
 * @since 2019.1
*######################## ajax动态内容请求
*原理等同于wnd_ajax_submit()表单提交，不同之处如下：
	使用本函数主要用以返回更复杂的结果如：表单，页面以弹窗或嵌入指定DOM的形式呈现，以供用户操作。表单提交则通常返回较为简单的结果
	允许携带一个参数，默认为0

	@since 2019.01.26
	若需要传递参数值超过一个，可将参数定义为GET参数形式如：'post_id=1&user_id=2'，后端采用:wp_parse_args() 解析参数

	实例：
		前端
		wnd_ajax_modal('xxx','post_id=1&user_id=2');

		后端
		function _wnd_xxx($args){
			$args = wp_parse_args($args)
			print_r($args);
		}
		弹窗将输出
		Array ( [post_id] => 1 [user_id] =>2)

*典型用途：
*	点击弹出登录框、点击弹出建议发布文章框
*/
// ajax 从后端请求内容，并以弹窗形式展现
function wnd_ajax_modal(handle, param = 0) {

	wnd_reset_modal();
	$.ajax({
		type: "POST",
		url: ajaxurl,
		data: {
			"handle": handle,
			"param": param,
			"ajax_type": "modal",
			"action_name": "_wnd_ajax_r",
			"action": "wnd_action"
		},
		//后台返回数据前
		beforeSend: function() {
			wnd_alert_msg("……")
		},
		//成功后
		success: function(response) {
			if (typeof response == "object") {
				wnd_alert_msg(response.msg);
			} else {
				wnd_alert_modal(response);
			}
		},
		// 错误
		error: function() {
			wnd_alert_msg("系统错误！");
		}
	});
}

/**
 *@since 2019.1.10  从后端请求ajax内容并填充到指定DOM
 **/
function wnd_ajax_embed(container, handle, param = 0) {

	$.ajax({
		type: "POST",
		url: ajaxurl,
		data: {
			"handle": handle,
			"param": param,
			"ajax_type": "embed",
			"action_name": "_wnd_ajax_r",
			"action": "wnd_action"
		},
		//后台返回数据前
		beforeSend: function() {
			// wnd_alert_msg("……")
		},
		//成功后
		success: function(response) {
			// 清除加载中效果
			wnd_reset_modal();

			if (typeof response == "object") {
				wnd_alert_msg(response.msg);
			} else {
				$(container).html(response);
			}
		},
		// 错误
		error: function() {
			wnd_alert_msg("系统错误！");
		}
	});

}

//############################### 根据表单id自动提交表单 并根据返回代码执行对应操作
function wnd_ajax_submit(form_id) {

	// 带有required属性的input 不能为空
	var input_value = true;
	$(form_id + " input").each(function() {
		var required = $(this).attr("required");
		if (required == "required") {
			if ($(this).val() == "") {
				input_value = false;
				$(this).addClass('is-danger');
				return false;
			}
			if ($(this).attr("type") == "radio" || $(this).attr("type") == "checkbox") {
				if ($('[name=' + $(this).attr("name") + ']:checked').length <= 0) {
					input_value = false;
					$(this).addClass('is-danger');
					return false;
				}
			}
		}
	});

	if (input_value === false) {
		wnd_ajax_msg('<span class="required">*</span>星标为必填项目！', 'is-danger', form_id);
		return false;
	}

	// 下拉必选，默认未选择 value = -1
	var option_value = true;
	$(form_id + " select option:selected").each(function() {
		// 查看当前下拉option的父元素select是否具有required属性
		var required = $(this).parent().attr("required");
		option_value = $(this).val();
		if (required == "required" && option_value == "-1") {
			option_value = false;
			$(form_id + " select").addClass('is-danger');
			return false;
		}
	});

	if (option_value === false) {
		wnd_ajax_msg('<span class="required">*</span>星标为必选项目！', "is-danger", form_id);
		return false;
	}

	// ########################### 提取wp_editor编辑器内容
	if ($(".wp-editor-area").length > 0) {
		// 获取编辑器id
		var wp_editor_id = $(".wp-editor-area").attr("id");
		var content;
		var editor = tinyMCE.get(wp_editor_id);
		if (editor) {
			content = editor.getContent();
		} else {
			content = $("#" + wp_editor_id).val();
		}
		$("#" + wp_editor_id).val(content);
	}

	// 生成表单数据
	var form_data = new FormData($(form_id).get(0));

	$.ajax({
		url: ajaxurl,
		dataType: "json",
		cache: false,
		contentType: false,
		processData: false,
		data: form_data,
		type: "post",
		beforeSend: showRequest,
		success: showResponse,
		error: showError
	});

	// 提交中
	function showRequest(formData, jqForm, options) {
		$(form_id + " [name='submit']").addClass("is-loading");
		$(form_id + " [name='submit']").attr("disabled", true);
	}

	// 返回结果
	function showResponse(response, statusText, xhr, $form) {

		$(form_id + " [name='submit']").removeClass("is-loading");

		if (response.status <= 0) {
			var color = "is-danger";
			$(form_id + " [name='submit']").attr("disabled", false);
		} else {
			var color = "is-success";
		}

		// 根据后端响应处理
		switch (response.status) {

			// 常规类，展示后端提示信息
			case 1:
				wnd_ajax_msg(response.msg, color, form_id);
				break;

				//更新类
			case 2:
				wnd_ajax_msg('提交成功！<a href="' + response.msg + '" target="_blank">查看</a>', color, form_id);
				break;

				// 跳转类
			case 3:
				wnd_ajax_msg("提交成功！", color, form_id);
				$(window.location).attr("href", response.msg);
				break;

				// 刷新当前页面
			case 4:
				window.location.reload(true);
				break;

				// 弹出信息并自动消失
			case 5:
				wnd_alert_msg(response.msg, 3);
				break;

				// 下载类
			case 6:
				wnd_ajax_msg("下载中……", color, form_id, 10);
				$(window.location).attr("href", response.msg);
				break;

				//默认展示提示信息
			default:
				wnd_ajax_msg(response.msg, color, form_id);
				break;
		}

	}

	// 提交错误
	function showError() {
		wnd_ajax_msg("系统错误！", "is-danger");
		$(form_id + " [name='submit']").removeClass("is-loading");
		$(form_id + " [name='submit']").attr("disabled", false);
	}

}

//####################### 文档加载完成后才能执行的功能
jQuery(document).ready(function($) {

	/**
	 *@since 2019.1.15 ajax 文件上传
	 */
	$("body").on("change", "[type='file']", function() {

		// 获取当前上传容器ID
		var id = "#" + $(this).data("id");
		//创建表单
		var form_data = new FormData();
		// 获取文件
		var file_data = $(this).prop("files")[0];

		// 获取属性
		var meta_key = $(id + " [name='file_meta_key']").val();
		var post_parent = $(id + " [name='file_post_parent']").val();
		var _ajax_nonce = $(id + " [name='file_upload_nonce']").val();
		var save_width = $(id + " [name='file_save_width']").val();
		var save_height = $(id + " [name='file_save_height']").val();
		var has_thumbnail = $(id + " [name='file_has_thumbnail']").val();

		// 组合表单数据
		form_data.append("file", file_data);
		form_data.append("meta_key", meta_key);
		form_data.append("post_parent", post_parent);
		form_data.append("_ajax_nonce", _ajax_nonce);
		form_data.append("save_width", save_width);
		form_data.append("save_height", save_height);
		form_data.append("action", "wnd_action");
		form_data.append("action_name", "wnd_upload_file");

		$.ajax({
			url: ajaxurl,
			dataType: "json",
			cache: false,
			contentType: false,
			processData: false,
			data: form_data,
			type: "post",
			//后台返回数据前
			beforeSend: function() {
				// wnd_ajax_msg("……")
				wnd_ajax_msg("正在处理，请勿关闭页面！", "is-warning", id);
			},
			// 提交成功
			success: function(response) {
				// 上传文件可能是多个，因此返回值为二维数组
				for (var i = 0, n = response.length; i < n; i++) {
					// 上传失败
					if (response[i].status === 0) {
						wnd_ajax_msg(response[i].msg, "is-danger", id);
						continue;
					}

					// 上传成功
					wnd_ajax_msg("上传成功！", "is-success", id);
					// 图像上传，更新缩略图
					if (has_thumbnail == 1) {
						$(id + " .thumb").prop("src", response[i].msg.url);
					} else {
						$(id + " .file-name").html('上传成功！<a href="' + response[i].msg.url + '">查看文件</a>');
					}
					$(id + " .delete").data("attachment-id", response[i].msg.id)
				}

			},
			// 错误
			error: function() {
				wnd_ajax_msg("系统错误！", "is-danger", id);
			}
		});

	});

	//  ################### 删除图片
	$("body").on("click", ".upload-field .delete", function() {

		if (!confirm("确定删除？")) {
			return false;
		}

		// 获取当前上传容器ID
		var id = "#" + $(this).data("id");
		// 获取被删除的附件ID
		var attachment_id = $(this).data("attachment-id");
		if (!attachment_id) {
			wnd_ajax_msg("没有可删除的文件！", "is-danger", id);
			return false;
		}

		//创建表单
		var form_data = new FormData();

		// 获取属性
		var meta_key = $(id + " [name='file_meta_key']").val();
		var post_parent = $(id + " [name='file_post_parent']").val();
		var _ajax_nonce = $(id + " [name='file_delete_nonce']").val();
		var has_thumbnail = $(id + " [name='file_has_thumbnail']").val();

		// 默认图
		var default_thumbnail = $(id + " [name='file_default_thumbnail']").val();

		// 组合表单数据
		form_data.append("meta_key", meta_key);
		form_data.append("post_parent", post_parent);
		form_data.append("attachment_id", attachment_id);
		form_data.append("_ajax_nonce", _ajax_nonce);
		// ajax handle
		form_data.append("action", "wnd_action");
		form_data.append("action_name", "wnd_delete_attachment");

		$.ajax({
			url: ajaxurl,
			dataType: "json",
			cache: false,
			contentType: false,
			processData: false,
			data: form_data,
			type: "post",
			//后台返回数据前
			beforeSend: function() {
				// wnd_ajax_msg("……")
				wnd_ajax_msg("删除中……", "is-warning", id);
			},
			// 上传成功
			success: function(response) {

				if (response.status === 0) {
					wnd_ajax_msg(response.msg, "is-danger", id);
					return false;
				}

				wnd_ajax_msg("已删除！", "is-success", id);
				// 图像删除还原为默认缩略图
				if (has_thumbnail == 1) {
					$(id + " .thumb").prop("src", default_thumbnail);
				} else {
					$(id + " .file-name").text('文件已删除……');
				}
				// 清空删除按钮数据绑定
				$(id + " .delete").data("attachment-id", 0)
			},
			// 错误
			error: function() {
				wnd_ajax_msg("系统错误！", "is-danger", id);
			}
		});

	});

	/**
	 *@since 2019.02.09 发送手机或邮箱验证码
	 */
	$("body").on("click", ".send-code", function() {

		// 清除定时器
		clearTimeout(send_countdown);
		wait = 90;

		// ajax中无法直接使用jQuery $(this)，需要提前定义
		var _this = $(this);
		var form_id = '#' + _this.parents('form').attr('id');
		var verity_type = $(this).data('verity-type');
		var send_type = $(this).data('send-type');
		var template = $(this).data('template');
		var nonce = $(this).data('nonce');

		var phone = $(form_id + " input[name='phone']").val();
		var _user_user_email = $(form_id + " input[name='_user_user_email']").val();
		if (_user_user_email == "" || phone == "") {
			wnd_ajax_msg("不知道发送到给谁……", "is-danger", form_id);
			return false;
		}

		$.ajax({
			type: "post",
			dataType: "json",
			url: ajaxurl,
			data: {
				action: 'wnd_action',
				action_name: "wnd_ajax_send_code",
				email: _user_user_email,
				phone: phone,
				verity_type: verity_type,
				send_type: send_type,
				template: template,
				_ajax_nonce: nonce
			},
			beforeSend: function() {
				_this.addClass("is-loading");
			},
			success: function(response) {

				if (response.status <= 0) {
					var color = "is-danger";
				} else {
					var color = "is-success";
					_this.attr("disabled", true);
					_this.text("已发送");
					wnd_send_countdown();
				}
				wnd_ajax_msg(response.msg, color, form_id);
				_this.removeClass("is-loading");
			},
			// 错误
			error: function() {
				wnd_ajax_msg("发送失败！", "is-danger", form_id);
				_this.removeClass("is-loading");
			}
		});

	});

	/**
	 *@since 2019.01.18 表单改变时，取消submit disable状态
	 */
	$("body").on("change", "form", function() {
		$("[name='submit']").attr("disabled", false);
		$("button").attr("disabled", false);
	});

	/**
	 *@since 2019.02.09 表单改变时，移除input警示状态
	 */
	$("body").on("input", "input", function() {
		$(this).removeClass('is-danger');
	});

	/**
	 *@since 2019.02.18 点击菜单 新增active
	 */
	$("body").on("click", ".menu a", function() {
		$(this).parents(".menu").find("a").removeClass("is-active");
		$(this).addClass("is-active");
	});

	/**
	 *@since 2019.02.18 点击Tabs 新增active
	 */
	$("body").on("click", ".tabs a", function() {
		$(this).parent("li").addClass("is-active");
		$(this).parent("li").siblings().removeClass("is-active");
	});

});