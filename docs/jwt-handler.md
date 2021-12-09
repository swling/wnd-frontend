# JWT Token Hanlder
将WordPress 账户体系与 JWT Token 绑定
- 支持标准 bear token
- 支持特定 cookie 传递 token

## Cookie 存储 Token 实例
- Cookie 存储 Token 可用于 Web 端跨域远程同步账户权限。
- 其他客户端 Token 应用需要根据实际应用场景，在客户端实现：Token 的存储，随请求发送，及退出清理。
```php
namespace Wndt\Utility;

use Wnd\Utility\Wnd_JWT_Handler;
use Wnd\Utility\Wnd_Singleton_Trait;

class Wndt_JWT_Handler {

	use Wnd_Singleton_Trait;

	protected $cookie_name;

	private function __construct() {
		$this->cookie_name = Wnd_JWT_Handler::$cookie_name;

		add_action('wp_login', [$this, 'handle_login'], 10, 2);
		add_action('wp_logout', [$this, 'handle_logout'], 10);
	}

	/**
	 * 处理用户登录
	 */
	public function handle_login($user_name, $user) {
		$this->save_client_token(Wnd_JWT_Handler::get_instance()->generate_token($user->ID));
	}

	/**
	 * 处理账户退出
	 */
	public function handle_logout() {
		$this->clean_client_token();
	}

	/**
	 * 删除客户端 Token
	 */
	protected function clean_client_token() {
		setcookie($this->cookie_name, '', time(), '/', $this->domain);
	}

	/**
	 * 客户端存储 Token
	 */
	protected function save_client_token($token) {
		setcookie($this->cookie_name, $token, $this->exp, '/', $this->domain, false, false);
	}
}

```

### 挂载
```php
add_action( 'init', function(){
	new Wndt\Utility\Wndt_JWT_handler();
} );
```

### Token 相关 Endpoint

#### 签发 JWT Token
根据第三方应用 openid 快速注册或登录到本应用，并签发对应的 JWT Token。
- Endpoint\Wnd_Issue_Token; 
- 该节点默认仅支持站内签发：即已通过 WP-Nonce 或cookie 完成了身份认证的，返回对应用户的 JWT token
- 若需用于第三方对接，需要继承节点类，创建子类，并完成对应配置：@see Endpoint\Wnd_Issue_Token;

### 综上，对接第三方应用时的典型应用场景如下：
- 第一步：根据第三方应用规则，获取到用户 openid，发送至与之对应的  Endpoint\Wnd_Issue_Token 的子类节点，完成在本应用的注册，并存储 JWT Token
- 第二步：用户请求中携带 JWT Token 以验证用户身份，并将第三方应用的基本资料快速同步到本应用（可选操作）
- 第三步：后续其他请求，均携带 JWT Token，即在本应用内被视为对应的已登录用户