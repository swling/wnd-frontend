/**
 * @link https://www.tiny.cloud/docs/ui-components/dialog/#dialogdataandstate
 * 这不是一个标准的 Tinymce 插件
 * 仅适用于 WordPress 插件：Wnd-Frontend
 */
tinymce.PluginManager.add('wndimage', function(editor, url) {
	let config = editor.getParam('wnd_config');
	var openDialog = function() {
		return editor.windowManager.open({
			title: '',
			body: {
				type: 'panel',
				items: [{
					type: 'dropzone',
					name: 'fileinput'
				}, {
					type: 'htmlpanel', // A HTML panel component
					html: '<div style="text-align:center;"><img id="wnd-single-img" src="" style="max-width: 100%;"></div>'
				}, {
					type: 'input',
					name: 'title',
					label: 'Title'
				}, ]
			},
			buttons: [{
				type: 'cancel',
				text: 'Close'
			}, {
				type: 'submit',
				text: 'Insert',
				primary: true
			}],
			img: {},
			// 提交
			onSubmit: function(api) {
				let data = api.getData();
				if (data.title) {
					img.alt = data.title;
				}
				img.removeAttribute('id');
				img.removeAttribute('style');
				editor.insertContent(img.outerHTML);
				api.close();
			},
			onChange: async function(api, details) {
				// 非文件字段变动
				if ('fileinput' !== details.name) {
					return false;
				}

				// 获取节点
				img = document.querySelector('#wnd-single-img');

				let data = api.getData();

				// 上传文件
				let file = data.fileinput[0];
				if (config.oss_sign_nonce) {
					upload_to_oss(file);
				} else {
					let formdata = new FormData();
					formdata.append('wnd_file[]', file);
					formdata.append('post_parent', config.post_parent);
					formdata.append('_ajax_nonce', config.upload_nonce);

					let file_info = await upload_to_local_server(formdata);
					img.src = file_info.url;
					img.dataset.id = file_info.id;
				}
			},
		});

		// Ajax
		async function upload_to_local_server(form_data) {
			let file_info = axios({
				url: config.upload_url,
				method: 'POST',
				data: form_data,
			}).then(res => {
				if (res.data.status <= 0) {
					return '';
				} else {
					return res.data[0].data;
				}
			}).catch(err => {
				console.log(err);
			});

			return file_info;
		}

		async function upload_to_oss(file) {
			wnd_load_md5_script(function() {
				/**
				 * 计算文件 MD5
				 * @link https://github.com/satazor/js-spark-md5
				 **/
				let blobSlice = File.prototype.slice || File.prototype.mozSlice || File.prototype.webkitSlice,
					chunkSize = 2097152, // Read in chunks of 2MB
					chunks = Math.ceil(file.size / chunkSize),
					currentChunk = 0,
					spark = new SparkMD5.ArrayBuffer(),
					fileReader = new FileReader();

				fileReader.onload = function(e) {
					spark.append(e.target.result); // Append array buffer
					currentChunk++;

					if (currentChunk < chunks) {
						loadNext();
					} else {
						let md5 = spark.end();
						upload(md5);
					}
				};

				fileReader.onerror = function() {
					console.warn('oops, something went wrong.');
				};

				function loadNext() {
					let start = currentChunk * chunkSize,
						end = ((start + chunkSize) >= file.size) ? file.size : start + chunkSize;

					fileReader.readAsArrayBuffer(blobSlice.call(file, start, end));
				}

				loadNext();
			});

			// 浏览器直传 OSS
			async function upload(md5) {
				let sign = await get_oss_sign(file, md5);
				let file_info = axios({
					url: sign.url,
					method: 'PUT',
					data: file,
					headers: sign.headers,
					/**
					 *  Access-Control-Allow-Origin 的值为通配符 ("*") ，而这与使用credentials相悖。
					 * @link https://developer.mozilla.org/zh-CN/docs/Web/HTTP/CORS/Errors/CORSNotSupportingCredentials
					 **/
					withCredentials: false,
				}).then(res => {
					if (res.status == 200) {
						img.src = sign.url;
						img.dataset.id = sign.id;
					} else {
						// 直传失败，应该删除对应 WP Attachment Post
						return '';
					}
				}).catch(err => {
					console.log(err);
				});

				return file_info;
			}

			// 获取浏览器 OSS 直传签名
			function get_oss_sign(file, md5) {
				let extension = file.name.split('.').pop();
				let mime_type = file.type;
				let sign = axios({
					url: config.oss_sign_endpoint,
					method: 'POST',
					data: {
						'_ajax_nonce': config.oss_sign_nonce,
						'extension': extension,
						'data': {
							'post_parent': config.post_parent
						},
						'mime_type': mime_type,
						'md5': md5,
					},
				}).then(res => {
					return res.data.data;
				})

				return sign;
			}
		}
	};

	// 注册一个工具栏按钮名称
	editor.ui.registry.addButton('wndimage', {
		icon: 'image',
		onAction: function() {
			openDialog();
		}
	});

	// 注册一个菜单项名称 menu/menubar
	editor.ui.registry.addMenuItem('wndimage', {
		text: 'Wnd Image',
		onAction: function() {
			openDialog();
		}
	});

	return {
		getMetadata: function() {
			return {
				//插件名和链接会显示在“帮助”→“插件”→“已安装的插件”中
				name: 'Wnd Image', //插件名称
				url: 'https://wndwp.com', //作者网址
			};
		}
	};
});