# 语言包制作与加载
本插件创立初衷是为了满足笔者自己的需求，故此，原生语言为中文简体。

本插件初始状态未加载语言包，原因是：为了追求极致的前台加载速度，本插件在配置选项中提供了一个禁用前台语言包的优化选项，即勾选此项后，在
网站前台，一律为英文语言。实测此环境下，WordPress 内核加载性能有质的提升。而如果插件默认加载了语言包，插件的交互界面也会跟随变为英文。
因此，插件默认是没有加载语言包的。

## 总结原因：
- 为了追求性能，笔者自身的站点前台语言一律为英文
- 插件本身是支持多语言的，如果默认加载语言包，会导致中英文错配
- 即笔者自身的网站主要为：WordPress 内核前台为英文、但需要整体内容呈现为中文

## 手动挂载语言包
上述配置对中文用户是没有影响的。但对于希望使用本插件制作英文网站的朋友，需要使用如下方法手动挂载插件语言包：
```php
/**
 * ## 方式一：加载插件内置的语言包（语言更新滞后）
 * 实际加载路径: 插件目录/languages/wnd-en_US.mo
 * @see 注意 action 优先级应该小于 10，否则本插件 init 阶段执行的代码将不会执行翻译
 */
add_action('init', function () {
    $domain = 'wnd';
    $locale = determine_locale();
    $mofile = $domain . '-' . $locale . '.mo';
    load_textdomain('wnd', WND_PATH . '/languages/' . $mofile);
}, 9, 1);

/**
 *  ## 方式一（推荐）：自行制作并加载语言包（本代码以主题为例）
 * 实际加载路径: 主题目录/languages/wnd-en_US.mo
 * @see 注意 action 优先级应该小于 10，否则本插件 init 阶段执行的代码将不会执行翻译
 */
add_action('init', function () {
    $domain = 'wnd';
    $locale = determine_locale();
    $mofile = $domain . '-' . $locale . '.mo';
    load_textdomain('wnd', TEMPLATEPATH . '/languages/' . $mofile);
}, 9, 1);

```

## 单站点多语言设切换置
```php
/**
*@since 2020.01.14
*在当前任意链接中新增 ?lang=xx 参数即可切换至对应语言
*注意：需参照前文，手动挂载语言包，且需要制作对应语言包支持
*/
$_GET['lang']
```