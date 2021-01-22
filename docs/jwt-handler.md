# JWT Token Hanlder
将WordPress 账户体系与 JWT Token 绑定。<br/>
本插件并未启用 JWT，如需启用 JWT，请在主题或插件中，继承 Wnd\Utility\Wnd_JWT_Handler; 创建子类并在 WP init Hook 中实例化。<br/>
子类需要完成客户端处理 Token 的具体方法，包含：存储、获取、删除。<br/>

## Cookie 存储 Token 实例
- Cookie 存储 Token 可用于 Web 端跨域远程同步账户权限。
- 其他客户端 Token 应用需要根据实际应用场景，在客户端实现：Token 的存储，随请求发送，及退出清理。
```php
namespace Wndt\Utility;

use Wnd\Utility\Wnd_JWT_Handler;

/**
 *@since 2021.01.20
 *
 */
class Wndt_JWT_Handler extends Wnd_JWT_Handler {

	protected $cookie_name = 'wnd_token';

	/**
	 *删除客户端 Token
	 */
	protected function clean_client_token() {
		setcookie($this->cookie_name, '', time(), '/', $this->domain);
	}

	/**
	 *获取客户端 JWT Token
	 */
	protected function get_client_token() {
		return $_COOKIE[$this->cookie_name] ?? '';
	}

	/**
	 *客户端存储 Token
	 */
	protected function save_client_token($token) {
		setcookie($this->cookie_name, $token, $this->exp, '/', $this->domain);
	}
}
```

### 挂载
```php
add_action( 'init', function(){
	new Wndt\Utility\Wndt_JWT_handler();
} );
```
