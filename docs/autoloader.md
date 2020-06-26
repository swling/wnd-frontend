
本插件定义自动加载规则如下文所述，符合自动加载的类文件，将实现自动加载。
## 规则定义
@see /wnd-autoloader.php

## 一、本插件：
- 类名称以 必须'Wnd_' 为前缀

```php
// 实例化
new Wnd\Model\Wnd_Auth;

// 实际加载文件
require 'includes/model/wnd-auth.php';
```

### 第三方组件：
component文件夹存储第三方组件，按通用驼峰命名规则
*注意：第三方组件文件及文件目录需要区分大小写，下同*
```php
// 实例化
new Wnd\Component\Aliyun\Sms\SignatureHelper;

// 实际加载文件
require 'includes/component/Aliyun/Sms/SignatureHelper.php';
```

## 二、主题
- 基本命名空间必须为：Wndt
- 类名称以 必须'Wndt_' 为前缀
```php
// 实例化
new Wndt\Module\Wndt_Bid_Form;

// 实际加载文件
require TEMPLATEPATH . '/includes/module/wndt-bid-form.php';
```

### 集成的第三方组件
```php
// 实例化
new Wndt\Component\AjaxComment;

// 实际加载文件
require TEMPLATEPATH . '/includes/component/AjaxComment.php';
```

### 其他插件
 - 基本命名空间必须为：WndPlugin
 - 插件具体类名称可自行定义，但需要与所在文件路径对应，以符合自动加载规则
 ```php
// 实例化
 new WndPlugin\Wndt_Demo\Wndt_Demo;

//  实际加载文件
 require WP_PLUGIN_DIR . '/wndt-demo/wndt-demo.php';
 ```

### 集成第三方组件
```php
new WndPlugin\Wndt_Demo\Component\AjaxComment;
require WP_PLUGIN_DIR . '/wndt-demo/component/AjaxComment.php';
```
