## 项目缘由
WordPress 5.0 及之后，几乎所有的更新都集中在古腾堡编辑器。而笔者自身的站点在可见的时间里，都不会需要这样一款编辑器。但无论站点是否需要，古腾堡编辑器对应的开销却一直存在，这并不是安装经典编辑器禁用古腾堡可以解决的，因为相关文件在 WP 初始化阶段就已经引入。处于对站点性能的苛求，笔者于 2021.07.22 尝试修改 WordPress 初始加载文件 wp-setting.php 发现效果显著。且通过复制wp-setting.php 到插件目录，并在 wp-config.php 中修改对应的引入文件，即可完美避免因为 WP 升级造成的文件覆盖。如此，便有了这个所谓的加速项目（实际称之为精简项目更合适，但无论如何，我的目的是为了让 WP 更快一点）。

### 工作原理
- 复制 WP 根目录下的 wp-setting.php 至本插件对应路径
- 选择性注释 wp-setting.php 中文件引入代码
- 修复因注释这些核心文件导致的错误，主要集中在，移除对应的 Hook，定义缺失的依赖函数详情参见 repair.php
- 修改 WP 根目录下 wp-config.php 引入修改后的 wp-setting.php

本项目会利用 Git 版本控制持续跟踪 WP 官方 wp-setting.php 的变动同步并更新。对后期 WP 可能新引入的核心功能，笔者将综合评估后，选择是否保留移除。相关决定将记录在《变更日志》。
  
### 实际效果
根据简单测试，相同站点精简后的 WordPress 系统，在开启 opcache 环境下优化效果如下：
 - 单个请求内存消耗降低超过 1M
 - 单个请求，耗时显著下降
 - 单个请求，文件加载数，降低约 90 个

目前尚未做更加严谨的并发测试等。

### 使用方法
如需使用精简版 WordPress，请配置网站根目录下的 wp-config.php 将
```php
require_once ABSPATH . 'wp-settings.php';
```

修改为：
```php
require_once ABSPATH . 'wp-content/plugins/wnd-frontend/wp-settings.php';
```
  
### 注意事项
 - 该文件需要最好配合 Wnd Frontend 插件使用，单独使用未经充分测试，可能出现未知 bug
  
 ### 变更日志
 - @2021.07.22 禁用 WP 原生 Rest API
 - @2021.07.22 移除古腾堡相关文件