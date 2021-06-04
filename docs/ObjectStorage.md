# 对象存储
对象存储支持服务商
 - OSS （阿里云）
 - COS （腾讯云）

## 代码演示
此处以阿里云 OSS 为例。
测试前，请确保后台填写了对应的 access 密钥对，且在对应云平台开通了对象存储服务。

```php
// 上传
$local_file     = '/home/www/upload/filename.jpg'
$endpoint       = 'https://xxx.aliyuxxx.com'; // 此处应填写完整的节点 URL 不要忘记 https:// 前缀
$file_path_name = '/dir/newfilename.jpg'; // 文件存储相对路径：包含子目录及文件名，直接填写文件名则上传至根目录

$object_storage = Wnd_Object_Storage::get_instance('Aliyun', $endpoint);
$object_storage->setFilePathName($file_path_name);
$object_storage->uploadFile($local_file);

// 删除 (参数释义同上)
$object_storage = Wnd_Object_Storage::get_instance('Aliyun', $endpoint);
$object_storage->setFilePathName($file_path_name);
$object_storage->deleteFile();
```