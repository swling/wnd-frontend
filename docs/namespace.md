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
- 类名称以'Wndt' 为前缀(不区分大小写)
- 命名空间为：
```php
namespace Wndt\Module;
```

## 用户自定义拓展Action响应
- 类名称以'Wndt' 为前缀(不区分大小写)
- 命名空间为：
```php
namespace Wndt\Action;
```

## 用户自定义拓展JsonGet响应
- 类名称以'Wndt' 为前缀(不区分大小写)
- 命名空间为：
```php
namespace Wndt\JsonGet;
```
