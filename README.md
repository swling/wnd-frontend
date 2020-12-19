# 万能的WordPress前端开发框架概述

## 授权声明
本项目开源，但非免费，使用前请务必知悉，以下情况中使用本插件需支付授权费用：
- ①用户主体为商业公司，盈利性组织。
- ②个人用户基于本插件二次开发，且以付费形式出售的产品。情节严重者，保留追究法律责任的权利。

## 联系方式
QQ：245484493  网站：https://wndwp.com

## 核心原理及功能结构概述
基于 WordPress Rest API 构建的前端 Ajax 交互系统。通过 Controller/Wnd_API：获取用户请求，转发至对应的层级以响应用户请求。可广泛用于构建各类会员系统，中小型客户及员工管理系统，报名及查询类系统，轻论坛，轻社交，垂直供需网站等。

API 转发响应结构及对应功能如下：
- Module：构建用户交互界面（本意命名为 interface 但该词为 PHP 保留关键词，容易引发各类冲突）
- Action：接收 Module 模块提交的数据请求，并执行对应操作
- Jsonget：Json 数据读取接口
- View/Wnd_Filter：筛选文章
- View/Wnd_User：筛选用户

未包含在上述结构的层级即为对应辅助类概述如下：
- Admin：WP 后台相关，如配置，菜单等
- Component：引入的第三方组件，如各类云平台产品 SDK 等
- Function：封装的通用函数
- Getway：第三方平台的结构拓展类，如短信，支付，验证码等（通常为某个类的子类）
- Hook：WordPress 原生及本插件的各类钩子（Action 及 Filter）
- Model：底层业务逻辑
- Template：封装的特定 UI 组件
- Utility：其他通用功能
- View：各类前端交互生成类，其中：View/Wnd_Filter、View/Wnd_User 参与 API 响应

## 功能列表
- 基于bulma框架，ajax 表单提交，ajax 弹窗模块，ajax 嵌入
- 强大的多重筛选 Wnd_Filter：支持 Ajax 操作，广泛用于各类管理面板，及网站内容筛选查询
- WordPress 前端内容增删改 (含权限控制filter)
- WordPress 前端用户注册登录更新，及管理员对普通用户的增删改(含权限控制 filter)
- WordPress 订单系统，预设文章付费阅读，付费下载(含权限控制 filter)
- 支付，短信模块
- 前端文件、图片上传
- 数组形式合并存储多个 user_meta、post_meta、option
- 基于bulma的表单生成类：Wnd_Form、Wnd_Form_WP、Wnd_Form_Post、Wnd_Form_User、Wnd_Form_Option 可快速生成各类前端表单
- 前端所有请求，均可快速添加签名校验，及腾讯阿里谷歌等第三方平台人机校验，有效防止机器灌水

*更多详情文档参见 /docs*