
# 注意事项
本插件出于数据库性能优化考虑，将大量普通信息以数组形式合并存储在 post meta 及 user meta。故，如未特别说明，字段均指 Wnd自定义数组字段，而非wp原生字段。

## 用户角色
- 普通注册用户的角色：author
- editor及以上角色定义为管理员 wnd_is_manager()

## 默认行为影响
- 启用插件后，会禁用WordPress原生注册及登录功能，普通用户无法WordPress后台，同时删除了普通注册用户一些不必要的user meta
- 启用插件后，会禁止WordPress原生的rest api

## 分类与标签关联
默认已支持WordPress原生post分类和标签关联。如需要支持自定义taxonomy，请遵循以下规则：
```php
$post_type.'_cat';//分类taxonomy
$post_type.'_tag';//标签taxonomy
```
## 分类名
在本插件中，如果同一个分类法（taxonomy）中存在多个同名分类，通过本插件内置的Wnd_Form_Post构造文章表单分类选项，将仅呈现最后一个分类。
为避免这种情况，请确保同一个分类法中，各个分类名称唯一。

# add_filter / add_action 
- priority: 10 为WordPress默认值，该值越大，表示越靠后执行
- 对于filter：可理解为值越大，当前add_filter的权重越高
- 对于action：越早执行可能通常理解为权重更高

# 自定义文章类型
*以下 post_type 并未均为私有属性('public' => false)，因此在WordPress后台无法查看到*
- 充值：recharge
- 消费、订单：order
- 站内信：mail
- 整站月度财务统计：stats-re(充值)、stats-ex(消费)

# 自定义文章状态
## success
用于功能型post、(如：充值，订单等) wp_insert_post 可直接写入未经注册的 post_status，但未经注册的post_status无法通过wp_query进行筛选，故此注册
## close
用于关闭文章相关功能，但不删除文章，保留前端可浏览

# 自定义文章版本
本插件自定义了一个文章版本功能，假定当前 $post ,则该 $post 版本定义：
```php
$revision['post_parent'] = $post->ID
$revision['post_type'] = $post->post_type

// 此外，为区分常规child post，在自定义revision中，设置wp post meta：
update_post_meta($revision_id, '_wnd_revision', 'true');
```
## 版本创建条件：
非管理员，编辑已公开发布过的文章

## 自定义版本作用：
普通用户发布文章，需要审核后才能公开展示，通过审核后的文章，如果用户需要再次修改，如果设定为pending状态，将导致原有post链接短暂失效。
在一些用户投稿较为活跃的站点，为避免这种情况，特别引入一个上述的自定义版本功能：

- 用户编辑已发布文章，将创建一个版本，并提交管理员审核。
- 管理员如果审核通过后，将已发布的文章平滑替换为版本内容，同时删除版本。
- 一篇文章，只创建一个版本。版本审核期间再次修改，原文，或版本，均直接修改现有已创建的版本，而非新建。

# 文章自定义字段
## WordPress原生字段
wp_post_meta: views (浏览量)
wp_post_meta: price (价格)

## wnd自定义字段
wnd_meta: file (存储付费附件的id)
wnd_meta: download_count (下载统计)
wnd_meta: order_count (订单统计，含15分钟以内未完成的订单)
wnd_meta: total_sales：付费内容总销售额
wnd_meta: total_commission：作者获得的总佣金
wnd_meta: attachment_records (累积上传到当前文章的附件总次数，含已删除，用于给附件自动设置 menu_order)
wnd_meta：gallery (文章相册，数组形式存放附件id)
wnd_meta: sku（产品 SKU，数组数据）

# 用户自定义字段
wnd_meta: money：余额
wnd_meta: expense：消费
wnd_meta: commission：佣金
wnd_meta: avatar：头像文件id
wnd_meta: avatar_url：头像外链
wnd_meta: locale：用户语言
<!-- wnd_meta: phone：用户手机号码 -->
<!-- wnd_meta: open_id：用户第三方openid -->
wnd_meta：gallery (用户相册)

wp_meta: status：用户状态['ok'，'banned']

# 充值、消费(自定义文章类型)
金额：post_content
关联：post_parent
标题：post_title
状态：post_status: pengding / success
类型：post_type：recharge / order
在线支付平台：post_excerpt (如 alipay 、wepay)

## 退款
wnd_meta : refund_count 	（分批）退款次数

wnd_meta : refund_records 	退款操作记录 
```php 
// 退款操作记录数据格式
 [
 	[
	'user_id'       => get_current_user_id(),
	'refund_amount' => $refund_amount,
	'time'          => time(),
	]
];
```

## 产品
wnd_meta : sku 	(array) SKU 信息

## 产品订单
wnd_meta : sku 		(array)	订单对应的产品 SKU 信息
wnd_meta : quantity (int) 	订单对应的产品数量

# 数据库

## wp_users:
如果需要用户昵称唯一：建议对display_name 新增索引

## wp_posts：
如需保证标题唯一：建议对post_title添加前缀索引

# 站内信功能
post_type => mail
post_status => 未读：pengding 已读: private

# wp_options
- 插件配置：wnd
- 自定义置顶文章：wnd_sticky_posts
- 置顶文章数据格式：二维数组 wnd_sticky_posts[$post_type]['post'.$post_id]

# 多语言设置
```php
/**
*@since 2020.01.14
*在当前任意链接中新增 ?lang=xx 参数即可切换至对应语言
*注意：需要对应语言包支持；暂只支持中英双语
*/
$_GET['lang']
```

# 统计浏览量
```JavaScript
<JavaScript>
wnd_ajax_update_views(post_id, interval = 3600);
</JavaScript>
```
