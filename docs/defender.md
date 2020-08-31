# 安全防护

## 使用方法
 - 启用拦截应该在加载WP之前，建议在 wp-config.php 中手动引入本文件
 - 防护依赖 Memcached 缓存、暂未支持 Redis
 - 仅依赖 PHP 及 Memcached，不依赖 WP 环境，可在其他非WP场景中使用

```php
/**
 *@since 0.8.61
 *
 */


// 引入文件
 require dirname(__FILE__) . '/wp-content/plugins/wnd-frontend/includes/utility/wnd-defender.php';

 /**
 *@param int 单ip检测时间段（秒）
 *@param int 但ip在检测时间段内的最大连接数
 *@param int 封锁时间（秒）
 */
 Wnd\Utility\Wnd_Defender::get_instance(60, 5, 1800);
 ```