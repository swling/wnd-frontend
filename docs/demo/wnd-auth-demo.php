<?php
use Wnd\Model\Wnd_Auth;

###########################################################
# 发送
$auth = Wnd_Auth::get_instance('xxx');
// 类型：register / reset_password / verify / bind
$auth->set_type('register');
// 验证码（可选，默认生成六位随机数字）
$auth->set_auth_code('6507080');
// 短信模板
$auth->set_template('324234');
// 发送
$auth->send();

# 验证
$auth = Wnd_Auth::get_instance('xxx');
$auth->set_type('register');
$auth->set_auth_code('6507080');
$auth->verify();
// 将当前邮箱/号码绑定到用户
$auth->reset_code($user_id);

# 删除
$auth = Wnd_Auth::get_instance('xxx');
$auth->delete();

###########################################################

# 绑定手机
$auth = Wnd_Auth::get_instance('xxx');
$auth->set_type('bind');
$auth->set_template('324234');
// 发送短信：$is_email = false / 发送邮件： $is_email = true
$auth->send();

# 验证绑定手机
$auth = Wnd_Auth::get_instance('xxx');
$auth->set_type('bind');
$auth->set_auth_code($auth_code);
// 已注册用户，已有数据记录，绑定成功后更新对应数据记录，并删除当前验证数据记录
$auth->verify($delete_after_verified = true);

###########################################################

# 绑定邮箱
$auth = Wnd_Auth::get_instance('xxx');
$auth->set_type('bind');
// 发送短信：$is_email = false / 发送邮件： $is_email = true
$auth->send();

# 验证绑定邮箱
$auth = Wnd_Auth::get_instance('xxx');
$auth->set_type('bind');
$auth->set_auth_code($auth_code);
// 已注册用户，已有数据记录，绑定成功后更新对应数据记录，并删除当前验证数据记录
$auth->verify($delete_after_verified = true);

###########################################################

# 验证已知用户操作
$auth = Wnd_Auth::get_instance($user);
$auth->set_type('reset_password');
$auth->set_auth_code($auth_code);
$auth->verify();
