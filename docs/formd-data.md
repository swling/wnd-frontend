# 前端Form name规则
本规则不再局限于表单，也通用于 json 格式的数据，前端请求数据遵循以下规则定义 name，后端即可自动提取。

## 常规内容型 Post
	文章：_post_{field}

	文章字段：
	_meta_{key} (*自定义数组字段)
	_wpmeta_{key} (*WordPress原生字段)
	_term_{taxonomy}(*taxonomy)

	用户：_user_{field}

	用户字段：
	_usermeta_{key} (*自定义数组字段)
	_wpusermeta_{key} (*WordPress原生字段)

## 交易类 Post （Model\Wnd_Transaction） 
- @since 0.9.52 交易类 Post 创建支持设置  meta 及 terms
- 请求数据规则同常规内容型 Post
- 用于拓展不同场景的 Transaction

## Option Form 规则
option表单将整个表单字段作为数组存储在一个特定 wp option 记录中，表单字段 name 规则如下：
```php
"_option_$option_name_$option_key"
```

## 手机验证表单：
	phone
	auth_code

# ajax表单响应代码表
@see static/js/main.js wnd_handle_response()