# 付费内容
必须设置价格
优先检测文件，如果设置了付费文件，则文章内容将全文输出

## 价格：
文章字段设置 wp_post_meta: price (此处使用独立字段，方便用户对付费和免费内容进行筛选区分)

### 内容：
用WordPress经典编辑器的more标签分割免费内容和付费内容(<!--more-->)
如不含more标签，则全文付费后可见

## 下载
文章字段：wnd_post_meta: file (存储上传附件的id)
下载计数：wnd_post_meta: download_count ;

# 创建支付
创建地址：
*do.php?action=payment&_wpnonce=wp_create_nonce('payment')*

表单字段：
post_id(GET/POST)：	如果设置了post_id 则表示该支付为订单类型，即为特定post付费，对应支付价格通过 wnd_get_post_price($post_id) 获取
money(GET/POST)：	如果未设置post_id 则表示该支付为充值类型，对应支付金额，即为表单提交数据
