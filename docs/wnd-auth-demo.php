<?php

# 发送
$auth = new Wnd_Auth;
// 类型：register / reset_password / verify
$auth->set_type('register');
// 号码或邮箱
$auth->set_email_or_phone('xxx');
// 验证码（可选，默认生成六位随机数字）
$auth->set_auth_code('6507080');
// 短信模板
$auth->set_template('324234');
// 发送
$auth->send();

# 验证
$auth = new Wnd_Auth;
$auth->set_type('register');
$auth->set_auth_code('6507080');
$auth->set_email_or_phone('xxx');
$auth->verify();
// 将当前邮箱/号码绑定到用户
$auth->reset_code($user_id = 16);

# 删除
$auth = new Wnd_Auth;
$auth->set_email_or_phone('xxx');
$auth->delete($user_id = 16);

# 绑定手机
$auth = new Wnd_Auth;
$auth->set_type('bind');
$auth->set_template('324234');
$auth->set_email_or_phone('xxx');
// 发送短信：$is_email = false / 发送邮件： $is_email = true
$auth->send();

# 绑定邮箱
$auth = new Wnd_Auth;
$auth->set_type('bind');
$auth->set_email_or_phone('xxx');
// 发送短信：$is_email = false / 发送邮件： $is_email = true
$auth->send();

# 验证当前登录用户
$auth = new Wnd_Auth;
$auth->set_type('verify');
$auth->send_to_current_user($is_email = true);
