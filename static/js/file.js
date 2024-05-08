/**
 * @since 0.9.39
 * 文件直传 OSS
 **/
async function _wnd_upload_to_oss(file, oss_sp, endpoint, direct = true, sign_data = {}) {
    let sign_action = direct ? 'wnd_sign_oss_direct' : 'wnd_sign_oss_upload';
    let fileData = new Blob([file]);
    let method = 'PUT';

    // 计算 MD5（腾讯 COS 必须）
    let md5 = await _wnd_md5_file(file);

    // 获取 OSS 签名，若为重复上传文件，直接返回文件信息
    let oss_sign = await get_oss_sign(md5);
    if (oss_sign.is_duplicate || false) {
        return oss_sign;
    }

    // 获取签名，上传文件，并将签名的结果合并写入实现约定的值
    let upload_res = axios({
        url: oss_sign.put_url,
        method: method,
        data: fileData,
        headers: oss_sign.headers,
        /**
         *  Access-Control-Allow-Origin 的值为通配符 ("*") ，而这与使用credentials相悖。
         * @link https://developer.mozilla.org/zh-CN/docs/Web/HTTP/CORS/Errors/CORSNotSupportingCredentials
         **/
        withCredentials: false,
    }).then(res => {
        return oss_sign;
        /**
         * 上传失败，WP 附件类上传，需要清空附件数据库记录 
         * @link https://github.com/axios/axios#handling-errors
         * 状态码不为 2xx 均视为失败
         **/
    }).catch(err => {
        if (!direct) {
            let meta_key = sign_data.meta_key;
            let attachment_id = oss_sign.id;
            _wnd_delete_attachment(attachment_id, meta_key);
        }
    });

    return upload_res;

    // 获取签名 
    function get_oss_sign(md5) {
        let extension = file.name.split('.').pop();
        let mime_type = file.type;
        let data = {
            'extension': extension,
            'mime_type': mime_type,
            'method': method,
            'oss_sp': oss_sp,
            'endpoint': endpoint,
            'md5': md5,
        };
        data = Object.assign(data, sign_data);

        let oss_sign = axios({
            url: wnd_action_api + '/common/' + sign_action,
            method: 'POST',
            data: data,
        }).then(res => {
            return res.data.data;
        })

        return oss_sign;
    }
}

/**
 * 按需加载 spark-md5 计算文件 md5
 * @link https://github.com/satazor/js-spark-md5
 */
async function _wnd_md5_file(file) {
    let md5_str = '';
    if ('undefined' == typeof SparkMD5) {
        let url = static_path + 'js/lib/spark-md5.min.js' + cache_suffix;
        await wnd_load_script(url);
    }
    md5_str = await MD5(file);
    return md5_str;

    /**
     * 使用 spark-md5 生成文件MD5摘要
     * @resolve {string} md5
     * @link https://www.jianshu.com/p/1694888bcae1
     */
    async function MD5(file) {
        return new Promise((resolve, reject) => {
            const blobSlice =
                File.prototype.slice ||
                File.prototype.mozSlice ||
                File.prototype.webkitSlice
            const chunkSize = 2097152 // Read in chunks of 2MB
            const chunks = Math.ceil(file.size / chunkSize)
            const spark = new SparkMD5.ArrayBuffer()
            const fileReader = new FileReader()
            let currentChunk = 0

            fileReader.onload = function (e) {
                spark.append(e.target.result) // Append array buffer
                currentChunk++

                if (currentChunk < chunks) {
                    loadNext()
                } else {
                    resolve(spark.end())
                }
            }

            fileReader.onerror = function (e) {
                reject(e)
            }

            function loadNext() {
                const start = currentChunk * chunkSize
                const end = start + chunkSize >= file.size ? file.size : start + chunkSize
                fileReader.readAsArrayBuffer(blobSlice.call(file, start, end))
            }

            loadNext()
        })
    }
}

/**
 * 发送删除附件请求 
 * @since 0.9.35
 */
function _wnd_delete_attachment(attachment_id, meta_key = '') {
    wnd_ajax_action('common/wnd_delete_file', {
        'file_id': attachment_id,
        'meta_key': meta_key,
    });
}


/**
 * @since 0.9.72
 * 前端直接删除 OSS 文件
 * 
 * let file = 'https://fenbu-ai.oss-cn-shanghai.aliyuncs.com/image.png';
 * _wnd_delete_oss_file(file, 'Aliyun', 'https://fenbu-ai.oss-cn-shanghai.aliyuncs.com');
 **/
async function _wnd_delete_oss_file(file, oss_sp, endpoint) {
    let sign_action = 'wnd_sign_oss_direct';
    let method = 'DELETE';
    // 获取 OSS 签名，若为重复上传文件，直接返回文件信息
    let oss_sign = await get_oss_sign(file);

    // 获取签名，上传文件，并将签名的结果合并写入实现约定的值
    return axios({
        url: oss_sign.delete_url,
        method: method,
        headers: oss_sign.headers,
        /**
         *  Access-Control-Allow-Origin 的值为通配符 ("*") ，而这与使用credentials相悖。
         * @link https://developer.mozilla.org/zh-CN/docs/Web/HTTP/CORS/Errors/CORSNotSupportingCredentials
         **/
        withCredentials: false,
    }).then(res => {
        return oss_sign;
        /**
         * 上传失败，WP 附件类上传，需要清空附件数据库记录 
         * @link https://github.com/axios/axios#handling-errors
         * 状态码不为 2xx 均视为失败
         **/
    }).catch(err => {
        console.error(err);
    });

    // 获取签名 
    function get_oss_sign() {
        let data = {
            'method': method,
            'oss_sp': oss_sp,
            'endpoint': endpoint,
            'file': file,
        };
        return axios({
            url: wnd_action_api + '/common/' + sign_action,
            method: 'POST',
            data: data,
        }).then(res => {
            return res.data.data;
        })
    }
}