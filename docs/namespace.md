# 命名空间
自动加载机制已统一转为小写，故命名空间不区分大小写

### 本插件
- 基本命名空间：Wnd,
- 对应本插件目录 /includes

### 主题
- 基本命名空间：Wndt
- 对应主题目录 TEMPLATEPATH/includes

### 插件
假设插件文件夹为 plugin-name
- 基本命名空间：Wnd_Plugin\Plugin_Name
- 对应WordPress插件 WP_PLUGIN_DIR/plugin-name

## 本插件实例

```php
// 模型类
namespace Wnd\Model;

// 视图类
namespace Wnd\View;

// 操作类
namespace Wnd\Action;

// 模块类：基于视图类的一些封装模块
namespace Wnd\Module;

// 数据类：读取json数据
namespace Wnd\Query;

// 拓展类
namespace Wnd\Component;
```
## 用户自定义拓展UI响应
命名空间为：
```php

// 主题
namespace Wndt\Module;

// 插件：PluginName需为插件实际名称，且插件名称与插件文件目录需符合自动加载规则，下同
namespace Wnd_Plugin\PluginName\Module;
```

## 用户自定义拓展Action响应
命名空间为：
```php
// 主题
namespace Wndt\Action;

// 插件
namespace Wnd_Plugin\PluginName\Action;
```

## 用户自定义拓展Query响应
命名空间为：
```php
// 主题
namespace Wndt\Query;

// 插件
namespace Wnd_Plugin\PluginName\Query;
```
以此类推……

## 拓展类命名规则
- 主题类名称以'Wndt'
- 插件类名称无特殊要求，但需要与所在路径对应，以符合自动加载规则

## 更多关于拓展类的详情参考
- api.md

## 关于自动加载规则参考
- autoloader.md
