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
@see static/js/wnd-frontend.js wnd_ajax_submit()