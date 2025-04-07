# Application
旨在提供付费 Action，付费 api 请求，具体实施细节需要在主题中完成。
总体思路为：
- 自定义 post type，并按规范设置 post slug 及 term slug，从而将 wp post 与主题 application 子类一一对应。
- 在主题 Action 层，继承 Wnd\Action\Wnd_Action_App 添加对应子类。
- 在 application 子类中，发起对应 Action 请求。

## App post
- 约定 post type             app
- 约定 自定义分类 taxonomy     app_cat
- 设置 post price 作为价格。支持设置 sku 以实现不同价格，但需要注意对不同 sku 匹配的请求参数做校验，防止前端篡改请求，导致价格错配。   

## 绑定 App post 与 application 子类
- 约定 application 子类命名空间为： "Wndt\Application\$category" （$category 为 App post 对应的分类 term slug）
- 约定 App post slug 与具体 application 子类名称对应匹配（不含 wndt_ 前缀，将自动转换 slug 间隔符为下划线）

* 技术细节参见：Wnd_App_abstract::get_app_name();

## 模版呈现
- 需要在主题中定义 App post 的专属模版，将对应 $application->render() 作为 App post 的主要网页内容呈现
- 根据需要，在主题 head 中调用 对应 application 的头部信息等
* 此步骤的前提是：已通过 App post 的 slug 和 category 将 post 与 application 子类一一对应

## 付费请求
- 在主题 Action 层，继承 Wnd\Action\Wnd_Action_App 添加对应子类，完成请求细节实现
- 通过 application 子类发起对应请求
- 在 Application 子类中需要定义 $application->action 属性，对应请求的 Action 类名称（不含命名空间）。
@see Wnd\Action\Wnd_Action_App::check();