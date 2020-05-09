# ajax交互概述：
后端返回json属性：status,msg,data

自定义action api：		wp-json/wnd/handler 	Allow: POST
自定义interface api：	wp-json/wnd/interface	Allow: GET
自定义filter api：		wp-json/wnd/filter	 	Allow: GET
自定义jsonget api：		wp-json/wnd/jsonget	 	Allow: GET

### action api
提交的数据中必须包含：
$_REQUEST['action']：该值为处理当前请求的控制类名称（不含命名空间）
$_REQUEST['_ajax_nonce']
nonce生成方式：wp_create_nonce($_REQUEST['action'])

#### @see Wnd_Form_WP->set_action

后端控制类接收数据并选择模型处理后，返回数组值：
['status'=>'状态值','msg'=>'消息','data'=>'数据'];
API统一将结果转为json格式，输出交付前端处理

#### 拓展操作（action）类
如需在第三方插件或主题拓展控制器处理请定义类并遵循以下规则：
- 类名称必须以wndt为前缀
- 命名空间必须为：Wndt\Action

### interface api
UI请求无需nonce校验需要包含如下参数
- $_GET['module']：该值为响应当前UI的类名称（不含命名空间）
- $_GET['param']：传递给UI类的参数(可选)
UI类将返回字符串（通常为HTML字符串）交付前端

### 拓展UI类
如需在第三方插件或主题拓展UI响应请定义类并遵循以下规则：
- 类名称必须以wndt为前缀
- 命名空间必须为：Wndt\Module

### jsonGet api
json获取请求无需nonce校验需要包含如下参数
- $_GET['data']：该值为需要获取的数据处理类名称（不含命名空间）
- $_GET['param']：传递给数据类的参数(可选)
返回为json数据

### 拓展jsonGet类
如需在第三方插件或主题拓展JsonGet请定义类并遵循以下规则：
- 类名称必须以wndt为前缀
- 命名空间必须为：Wndt\JsonGet

## @see /includes/controller/class-wnd-api.php
