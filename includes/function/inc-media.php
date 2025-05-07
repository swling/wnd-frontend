<?php

// 获取附件数据
function wnd_get_attachment(int $attachment_id): mixed {
	$instance = Wnd\WPDB\Wnd_Attachment_DB::get_instance();
	$data     = $instance->get_by('ID', $attachment_id);
	if (!$data) {
		return false;
	}

	return $data;
}

// 获取附件的绝对 URL
function wnd_get_attachment_url(int $attachment_id): string {
	$path = wnd_get_attachment_path($attachment_id);
	if (!$path) {
		return '';
	}

	$uploads = wp_get_upload_dir();
	$url     = $uploads['baseurl'] . '/' . $path;
	$url     = apply_filters('wnd_get_attachment_url', $url, $attachment_id);

	return $url;
}

// 获取附件的绝对路径
function wnd_get_attachment_path(int $attachment_id): string {
	$data = wnd_get_attachment($attachment_id);
	if (!$data) {
		return '';
	}

	return $data->file_path ?? '';
}

function wnd_delete_attachment(int $attachment_id) {
	$instance = Wnd\WPDB\Wnd_Attachment_DB::get_instance();
	return $instance->delete_by('ID', $attachment_id);
}

function wnd_delete_attachment_file(int $attachment_id): bool {
	$uploadpath = wp_get_upload_dir();
	$path       = wnd_get_attachment_path($attachment_id);

	$file = $uploadpath['basedir'] . "/$path";
	return wp_delete_file_from_directory($file, $uploadpath['basedir']);
}

function wnd_delete_attachment_by_post(int $post_id) {
	$instance = Wnd\WPDB\Wnd_Attachment_DB::get_instance();
	return $instance->delete_by('post_id', $post_id);
}

/**
 * 下载文件
 * 通过php脚本的方式将文件发送到浏览器下载，避免保留文件的真实路径
 * 然而，用户仍然可能通过文件名和网站结构，猜测到可能的真实路径，
 * 因此建议将$file定义在网站目录之外，这样通过任何url都无法访问到文件存储目录
 * 主要用户付费下载
 * @since 初始化
 *
 * @param string $the_file 	本地或远程完整文件地址
 * @param string $rename   发送给浏览器的文件名称，重命名后可防止在收费类下载场景中，用户通过文件名猜测路径
 */
function wnd_download_file($file, $rename = 'download') {
	// 获取文件后缀信息
	$ext      = wnd_parse_file_ext($file);
	$filename = urlencode(get_option('blogname') . '-' . $rename . '.' . $ext);

	// Force download
	header('Content-type: application/octet-stream');
	header('Content-Disposition: attachment; filename*=UTF-8\'\'' . $filename);
	readfile($file);
	exit;
}

/**
 * 保存文章中的外链图片，并替换html图片地址
 * @since 2019.01.22
 *
 * @param  string 	content
 * @param  string 	$upload_dir
 * @param  int    	$post_id
 * @return string 	$content 	经过本地化后的内容
 */
function wnd_download_remote_images($content, $upload_dir, $post_id) {
	if (empty($content)) {
		return;
	}

	$preg = preg_match_all('/<img.*?src="(.*?)"/', stripslashes($content), $matches);
	if ($preg) {
		$i = 1;
		foreach ($matches[1] as $image_url) {
			if (empty($image_url)) {
				continue;
			}

			$pos = strpos($image_url, $upload_dir); // 判断图片链接是否为外链
			if (false === $pos) {
				$local_url = wnd_download_remote_image($image_url, $post_id, time() . '-' . $i);
				if (!is_wp_error($local_url)) {
					$content = str_replace($image_url, $local_url, $content);
				}
			}
			$i++;
		}
		unset($image_url);
	}

	return $content;
}

/**
 * WordPress 远程下载图片 并返回上传后的图片地址/html 或 id
 * @since 2019.01.22
 *
 * @param  string          	$url         	远程URL
 * @param  int             	$post_parent 	需要附属到的Post ID
 * @param  string          	$title       	文件名称
 * @param  string          	$return      	Optional. Accepts 'html' (image tag html) or 'src' (URL), or 'id' (attachment ID). Default 'html'.
 * @return string|WP_Error Populated HTML img tag on success, WP_Error object otherwise.
 */
function wnd_download_remote_image($url, $post_parent, $title, $return = 'src') {
	return wnd_media_sideload($url, $post_parent, $title, $return);
}

/**
 * Downloads an file from the specified URL and attaches it to a post.
 * post meta value. or 'id' (attachment ID). Default 'html'.
 *
 * 相较于Wp函数，更新了多前端调用的默认支持，更新了对图像外文件下载的支持，并自动将文件随机重命名
 * @see media_sideload_image
 *
 * @param  string          $file     The URL of the image to download.
 * @param  int             $post_id  Optional. The post ID the media is to be associated with.
 * @param  string          $desc     Optional. Description of the image.
 * @param  string          $return   Optional. Accepts 'html' (image tag html) or 'src' (URL),
 * @return string|WP_Error Populated HTML img tag on success, WP_Error object otherwise.
 */
function wnd_media_sideload($file, $post_id, $desc = '', $return = 'id') {
	if (!function_exists('media_handle_sideload')) {
		require ABSPATH . 'wp-admin/includes/media.php';
		require ABSPATH . 'wp-admin/includes/file.php';
		require ABSPATH . 'wp-admin/includes/image.php';
	}

	if (!empty($file)) {
		$file_array = [];

		// 提取文件后缀
		$ext = wnd_parse_file_ext($file);
		if (!$ext) {
			return new WP_Error('Invalid file URL.');
		}

		$file_array['name'] = 'sync' . uniqid() . '.' . $ext;

		// Download file to temp location.
		$file_array['tmp_name'] = download_url($file);

		// If error storing temporarily, return the error.
		if (is_wp_error($file_array['tmp_name'])) {
			return $file_array['tmp_name'];
		}

		// Do the validation and storage stuff.
		$id = media_handle_sideload($file_array, $post_id, $desc);

		// If error storing permanently, unlink.
		if (is_wp_error($id)) {
			@unlink($file_array['tmp_name']);
			return $id;
		}

		// Store the original attachment source in meta.
		// add_post_meta($id, '_source_url', $file);

		// If attachment id was requested, return it.
		if ('id' === $return) {
			return $id;
		}

		$src = wp_get_attachment_url($id);
	}

	// Finally, check to make sure the file has been saved, then return the HTML.
	if (!empty($src)) {
		if ('src' === $return) {
			return $src;
		}

		if ('id' === $return) {
			return $id;
		}

		$alt  = isset($desc) ? esc_attr($desc) : '';
		$html = "<img src='$src' alt='$alt' />";

		return $html;
	} else {
		return new WP_Error('image_sideload_failed');
	}
}

/**
 * 根据文件路径或 URL 提取文件后缀名
 * @since 0.9.35.5
 */
function wnd_parse_file_ext(string $file): string {
	$url_info = parse_url($file);
	$path     = $url_info['path'] ?? '';
	$query    = $url_info['query'] ?? '';
	$scheme   = $url_info['scheme'] ?? '';
	if (!$path) {
		return '';
	}

	$info = pathinfo($path);
	$ext  = $info['extension'] ?? '';

	// 带有处理参数的 url 可能实际文件类型不是后缀名
	if ($scheme and $query) {
		$content_type = get_headers($file, 1)['Content-Type'];
		$ext          = wnd_mime2ext($content_type);
	}

	return $ext;
}

/**
 * Content Type 转文件后缀名
 * @since 0.9.35.5
 */
function wnd_mime2ext(string $mime): string {
	$mime_map = [
		'video/3gpp2'                                                               => '3g2',
		'video/3gp'                                                                 => '3gp',
		'video/3gpp'                                                                => '3gp',
		'application/x-compressed'                                                  => '7zip',
		'audio/x-acc'                                                               => 'aac',
		'audio/ac3'                                                                 => 'ac3',
		'application/postscript'                                                    => 'ai',
		'audio/x-aiff'                                                              => 'aif',
		'audio/aiff'                                                                => 'aif',
		'audio/x-au'                                                                => 'au',
		'video/x-msvideo'                                                           => 'avi',
		'video/msvideo'                                                             => 'avi',
		'video/avi'                                                                 => 'avi',
		'application/x-troff-msvideo'                                               => 'avi',
		'application/macbinary'                                                     => 'bin',
		'application/mac-binary'                                                    => 'bin',
		'application/x-binary'                                                      => 'bin',
		'application/x-macbinary'                                                   => 'bin',
		'image/bmp'                                                                 => 'bmp',
		'image/x-bmp'                                                               => 'bmp',
		'image/x-bitmap'                                                            => 'bmp',
		'image/x-xbitmap'                                                           => 'bmp',
		'image/x-win-bitmap'                                                        => 'bmp',
		'image/x-windows-bmp'                                                       => 'bmp',
		'image/ms-bmp'                                                              => 'bmp',
		'image/x-ms-bmp'                                                            => 'bmp',
		'application/bmp'                                                           => 'bmp',
		'application/x-bmp'                                                         => 'bmp',
		'application/x-win-bitmap'                                                  => 'bmp',
		'application/cdr'                                                           => 'cdr',
		'application/coreldraw'                                                     => 'cdr',
		'application/x-cdr'                                                         => 'cdr',
		'application/x-coreldraw'                                                   => 'cdr',
		'image/cdr'                                                                 => 'cdr',
		'image/x-cdr'                                                               => 'cdr',
		'zz-application/zz-winassoc-cdr'                                            => 'cdr',
		'application/mac-compactpro'                                                => 'cpt',
		'application/pkix-crl'                                                      => 'crl',
		'application/pkcs-crl'                                                      => 'crl',
		'application/x-x509-ca-cert'                                                => 'crt',
		'application/pkix-cert'                                                     => 'crt',
		'text/css'                                                                  => 'css',
		'text/x-comma-separated-values'                                             => 'csv',
		'text/comma-separated-values'                                               => 'csv',
		'application/vnd.msexcel'                                                   => 'csv',
		'application/x-director'                                                    => 'dcr',
		'application/vnd.openxmlformats-officedocument.wordprocessingml.document'   => 'docx',
		'application/x-dvi'                                                         => 'dvi',
		'message/rfc822'                                                            => 'eml',
		'application/x-msdownload'                                                  => 'exe',
		'video/x-f4v'                                                               => 'f4v',
		'audio/x-flac'                                                              => 'flac',
		'video/x-flv'                                                               => 'flv',
		'image/gif'                                                                 => 'gif',
		'application/gpg-keys'                                                      => 'gpg',
		'application/x-gtar'                                                        => 'gtar',
		'application/x-gzip'                                                        => 'gzip',
		'application/mac-binhex40'                                                  => 'hqx',
		'application/mac-binhex'                                                    => 'hqx',
		'application/x-binhex40'                                                    => 'hqx',
		'application/x-mac-binhex40'                                                => 'hqx',
		'text/html'                                                                 => 'html',
		'image/x-icon'                                                              => 'ico',
		'image/x-ico'                                                               => 'ico',
		'image/vnd.microsoft.icon'                                                  => 'ico',
		'text/calendar'                                                             => 'ics',
		'application/java-archive'                                                  => 'jar',
		'application/x-java-application'                                            => 'jar',
		'application/x-jar'                                                         => 'jar',
		'image/jp2'                                                                 => 'jp2',
		'video/mj2'                                                                 => 'jp2',
		'image/jpx'                                                                 => 'jp2',
		'image/jpm'                                                                 => 'jp2',
		'image/jpeg'                                                                => 'jpeg',
		'image/pjpeg'                                                               => 'jpeg',
		'application/x-javascript'                                                  => 'js',
		'application/json'                                                          => 'json',
		'text/json'                                                                 => 'json',
		'application/vnd.google-earth.kml+xml'                                      => 'kml',
		'application/vnd.google-earth.kmz'                                          => 'kmz',
		'text/x-log'                                                                => 'log',
		'audio/x-m4a'                                                               => 'm4a',
		'application/vnd.mpegurl'                                                   => 'm4u',
		'audio/midi'                                                                => 'mid',
		'application/vnd.mif'                                                       => 'mif',
		'video/quicktime'                                                           => 'mov',
		'video/x-sgi-movie'                                                         => 'movie',
		'audio/mpeg'                                                                => 'mp3',
		'audio/mpg'                                                                 => 'mp3',
		'audio/mpeg3'                                                               => 'mp3',
		'audio/mp3'                                                                 => 'mp3',
		'video/mp4'                                                                 => 'mp4',
		'video/mpeg'                                                                => 'mpeg',
		'application/oda'                                                           => 'oda',
		'audio/ogg'                                                                 => 'ogg',
		'video/ogg'                                                                 => 'ogg',
		'application/ogg'                                                           => 'ogg',
		'application/x-pkcs10'                                                      => 'p10',
		'application/pkcs10'                                                        => 'p10',
		'application/x-pkcs12'                                                      => 'p12',
		'application/x-pkcs7-signature'                                             => 'p7a',
		'application/pkcs7-mime'                                                    => 'p7c',
		'application/x-pkcs7-mime'                                                  => 'p7c',
		'application/x-pkcs7-certreqresp'                                           => 'p7r',
		'application/pkcs7-signature'                                               => 'p7s',
		'application/pdf'                                                           => 'pdf',
		'application/octet-stream'                                                  => 'pdf',
		'application/x-x509-user-cert'                                              => 'pem',
		'application/x-pem-file'                                                    => 'pem',
		'application/pgp'                                                           => 'pgp',
		'application/x-httpd-php'                                                   => 'php',
		'application/php'                                                           => 'php',
		'application/x-php'                                                         => 'php',
		'text/php'                                                                  => 'php',
		'text/x-php'                                                                => 'php',
		'application/x-httpd-php-source'                                            => 'php',
		'image/png'                                                                 => 'png',
		'image/x-png'                                                               => 'png',
		'application/powerpoint'                                                    => 'ppt',
		'application/vnd.ms-powerpoint'                                             => 'ppt',
		'application/vnd.ms-office'                                                 => 'ppt',
		'application/msword'                                                        => 'doc',
		'application/vnd.openxmlformats-officedocument.presentationml.presentation' => 'pptx',
		'application/x-photoshop'                                                   => 'psd',
		'image/vnd.adobe.photoshop'                                                 => 'psd',
		'audio/x-realaudio'                                                         => 'ra',
		'audio/x-pn-realaudio'                                                      => 'ram',
		'application/x-rar'                                                         => 'rar',
		'application/rar'                                                           => 'rar',
		'application/x-rar-compressed'                                              => 'rar',
		'audio/x-pn-realaudio-plugin'                                               => 'rpm',
		'application/x-pkcs7'                                                       => 'rsa',
		'text/rtf'                                                                  => 'rtf',
		'text/richtext'                                                             => 'rtx',
		'video/vnd.rn-realvideo'                                                    => 'rv',
		'application/x-stuffit'                                                     => 'sit',
		'application/smil'                                                          => 'smil',
		'text/srt'                                                                  => 'srt',
		'image/svg+xml'                                                             => 'svg',
		'application/x-shockwave-flash'                                             => 'swf',
		'application/x-tar'                                                         => 'tar',
		'application/x-gzip-compressed'                                             => 'tgz',
		'image/tiff'                                                                => 'tiff',
		'text/plain'                                                                => 'txt',
		'text/x-vcard'                                                              => 'vcf',
		'application/videolan'                                                      => 'vlc',
		'text/vtt'                                                                  => 'vtt',
		'audio/x-wav'                                                               => 'wav',
		'audio/wave'                                                                => 'wav',
		'audio/wav'                                                                 => 'wav',
		'application/wbxml'                                                         => 'wbxml',
		'video/webm'                                                                => 'webm',
		'audio/x-ms-wma'                                                            => 'wma',
		'application/wmlc'                                                          => 'wmlc',
		'video/x-ms-wmv'                                                            => 'wmv',
		'video/x-ms-asf'                                                            => 'wmv',
		'application/xhtml+xml'                                                     => 'xhtml',
		'application/excel'                                                         => 'xl',
		'application/msexcel'                                                       => 'xls',
		'application/x-msexcel'                                                     => 'xls',
		'application/x-ms-excel'                                                    => 'xls',
		'application/x-excel'                                                       => 'xls',
		'application/x-dos_ms_excel'                                                => 'xls',
		'application/xls'                                                           => 'xls',
		'application/x-xls'                                                         => 'xls',
		'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'         => 'xlsx',
		'application/vnd.ms-excel'                                                  => 'xlsx',
		'application/xml'                                                           => 'xml',
		'text/xml'                                                                  => 'xml',
		'text/xsl'                                                                  => 'xsl',
		'application/xspf+xml'                                                      => 'xspf',
		'application/x-compress'                                                    => 'z',
		'application/x-zip'                                                         => 'zip',
		'application/zip'                                                           => 'zip',
		'application/x-zip-compressed'                                              => 'zip',
		'application/s-compressed'                                                  => 'zip',
		'multipart/x-zip'                                                           => 'zip',
		'text/x-scriptzsh'                                                          => 'zsh',
	];

	return $mime_map[$mime] ?? '';
}

/**
 * 根据 post id 获取付费文件 URL
 * @since 0.9.26
 */
function wnd_get_paid_file(int $post_id): string {
	$file = wnd_get_post_meta($post_id, 'file_url') ?: '';
	if ($file) {
		return $file;
	}

	$file_id = wnd_get_paid_file_id($post_id);
	return wnd_get_attachment_url($file_id) ?: '';
}

/**
 * 根据 post id 获取付费文件 Attachment ID
 * @since 0.9.35.5
 */
function wnd_get_paid_file_id(int $post_id): int {
	return wnd_get_post_meta($post_id, 'file') ?: 0;
}

/**
 * 需要将图像存储在阿里云oss，并利用filter对wp_get_attachment_url重写为阿里oss地址
 * 阿里云的图片处理
 * 截至2019.05.11图片处理定价：每月0-10TB：免费 >10TB：0.025元/GB
 * @link https://help.aliyun.com/document_detail/44688.html
 * @since 2019.05.08 获取图像缩略图
 *
 * @param int|string 	$is_or_url 	附件post id 或者oss完整图片地址
 * @param int        			$width   		图片宽度
 * @param int        			$height  		图片高度
 */
function wnd_get_thumbnail_url($id_or_url, $width = 160, $height = 120) {
	$url = is_numeric($id_or_url) ? wnd_get_attachment_url($id_or_url) : $id_or_url;
	if (!$url) {
		return false;
	}

	return $url . '?x-oss-process=image/resize,m_fill,w_' . $width . ',h_' . $height;
}

/**
 * 核查文件类型是否在允许列表中
 * @since 0.9.57.7
 */
function wnd_is_allowed_extension(string $extension): bool {
	$allowed_extensions = array_keys(get_allowed_mime_types());
	$extensions         = [];
	foreach ($allowed_extensions as $value) {
		$extensions = array_merge($extensions, explode('|', $value));
	}
	unset($value);

	return in_array($extension, $extensions);
}
