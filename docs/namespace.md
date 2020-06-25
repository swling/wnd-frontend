# 命名空间
自动加载机制已统一转为小写，故命名空间不区分大小写
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
namespace Wnd\JsonGet;

// 拓展类
namespace Wnd\Component;
```
## 用户自定义拓展UI响应
- 命名空间为：
```php

// 主题
namespace Wndt\Module;

// 插件：PluginName需为插件实际名称，且插件名称与插件文件目录需符合自动加载规则，下同
namespace Wndp\PluginName\Module;
```

## 用户自定义拓展Action响应
- 命名空间为：
```php
// 主题
namespace Wndt\Action;

// 插件
namespace Wndp\PluginName\Action;
```

## 用户自定义拓展JsonGet响应
- 命名空间为：
```php
// 主题
namespace Wndt\JsonGet;

// 插件
namespace Wndp\PluginName\JsonGet;
```
以此类推……

## 拓展类命名规则
主题类名称以'Wndt'，插件类名称以'wndp'为前缀(不区分大小写)

## 更多关于拓展类的详情参考
- api.md

## 关于自动加载规则参考
- autoloader.md
