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

### Token 相关 Endpoint

#### 签发 JWT Token
根据第三方应用 openid 快速注册或登录到本应用，并签发对应的 JWT Token。
- Endpoint\Wnd_Issue_Token; 
- 该节点为抽象节点，需在子类中实现对实际第三方应用 openid 的获取
- 支持在本应用内部调用即：通过 WP-Nonce 完成了身份认证，同样会返回对应用户的 JWT token

#### 同步用户 Profile
快速将第三方应用用户的基本资料，如头像、昵称等，同步到本应用。
- Endpoint\Wnd_Sync_Profile;
- 该节点包含可通过 Action 拓展相关操作：
```php
do_action('wnd_sync_profile', $user_id, $this->data);
```

综上，对接第三方应用时的典型应用场景如下：
- 第一步：根据第三方应用规则，获取到用户 openid，发送至与之对应的  Endpoint\Wnd_Issue_Token 的子类节点，完成在本应用的注册，并存储 JWT Token
- 第二步：用户请求中携带 JWT Token 以验证用户身份，并将第三方应用的基本资料快速同步到本应用（可选操作）
- 第三步：后续其他请求，均携带 JWT Token，即在本应用内被视为对应的已登录用户