# ajax交互概述：
后端返回json属性：status,msg,data

自定义action api：		wp-json/wnd/handler 	Allow: POST
自定义interface api：	wp-json/wnd/interface	Allow: GET
自定义posts api：		wp-json/wnd/posts	 	Allow: GET
自定义users api：		wp-json/wnd/users	 	Allow: GET
自定义jsonget api：		wp-json/wnd/jsonget	 	Allow: GET

## Wnd_API 定义 
@see /includes/controller/wnd-api.php

## 自动加载规则 
@see autoloader.md

### action api
提交的数据中必须包含：
$_POST['action']：该值为处理当前请求的控制类名称（不含命名空间）
$_POST['_ajax_nonce']
nonce生成方式：wp_create_nonce($_POST['action'])

#### @see Wnd_Form_WP->set_action

后端控制类接收数据并选择模型处理后，返回数组值：
['status'=>'状态值','msg'=>'消息','data'=>'数据'];
API统一将结果转为json格式，输出交付前端处理

### interface api
UI请求无需nonce校验需要包含如下参数
- $_GET['module']：该值为响应当前UI的类名称（不含命名空间）
- 传参请直接通过 $_GET
UI类将返回字符串（通常为HTML字符串）交付前端


### jsonGet api
json获取请求无需nonce校验需要包含如下参数
- $_GET['data']：该值为需要获取的数据处理类名称（不含命名空间）
- 传参请直接通过 $_GET
返回为json数据

### 前端请求举例（以Module为例）
```JavaScript
wnd_ajax_modal("wnd_user_center", {"x": "xxx"});
```
实际类名称：
```php
Wnd\Module\Wnd_User_Center::render(['x' => 'xxx']);
```


## 拓展APIs

### 主题拓展
如需在主题中拓展API遵循以下规则（以Module为例，其他API以此类推）：
- 基本命名空间必须为：Wndt
- Module 命名空间必须为：Wndt\Module
- 类名称必须以 'wndt_' 为前缀
- 文件夹路径：{TEMPLATEPATH}/includes/module
实例：
```php
// 实例化
new Wndt\Module\Wndt_Bid_Form;

// 自动加载路径
require TEMPLATEPATH . '/includes/module/wndt-bid-form.php';
```
前端请求举例（以Module为例）
```JavaScript
wnd_ajax_modal("Wndt_Bid_Form", {"x": "xxx"}); 
```
实际类名称：
```php
Wndt\Module\Wndt_Bid_Form::render(['x' => 'xxx']);
```

### 插件拓展
如需在插件中拓展API类需遵循以下规则（以Module为例，其他API以此类推）:
- 基本命名空间必须为：Wnd_Plugin\$plugin_name;
- Module 命名空间必须为：Wnd_Plugin\$plugin_name\module
- 插件具体类名称可自行定义，但需要与所在文件路径对应，以符合自动加载规则 
- 文件路径：{WP_PLUGIN_DIR}/$plugin_name/includes/module
- *注意插件文件夹不得使用下划线，插件文件夹与插件命名空间需要符合自动加载规则：（名称对应，类名称下划线对应文件夹间隔符）*
实例：
```php
// 实例化
 new Wnd_Plugin\Wndt_File_Import\Module\Wndt_Demo;

// 自动加载路径
 require WP_PLUGIN_DIR . '/wndt-file-import/module/wndt-demo.php';
```
#### 插件特有属性
前端ajax请求插件API拓展，需要额外提供插件名称。
本插件及主题拓展的文件路径具有唯一性，且分别强制要求以 'Wnd_'、'wndt_'为前缀，因此可自动根据前缀判定当前类归属
第三方拓展插件具有多样性，因此需要额外提供插件名，以确定具体加载路径。
拓展插件类请求格式：$plugin_name\\$class_name
$class_name 不含命名空间（api将根据类名称前缀自动解析补全）

假定插件：Wndt_File_Import 具有一个 Wndt_Demo UI模块，前端请求实例如下：
```JavaScript
wnd_ajax_modal("Wndt_File_Import\\Wndt_Demo",  {"x": "xxx"});
```
实际类名称：
```php
Wnd_Plugin\Wndt_File_Import\Module\Wndt_Demo::render(['x' => 'xxx']);
```
### 拓展中添加第三方组件
{plugin_or_theme_dir}/includes/component文件夹存储第三方组件，按通用驼峰命名规则
(注意：第三方组件文件及文件目录需要区分大小写)
