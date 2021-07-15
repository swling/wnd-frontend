/**
 * @link https://www.tiny.cloud/docs/ui-components/dialog/#dialogdataandstate
 * 这不是一个标准的 Tinymce 插件
 * 仅适用于 WordPress 插件：Wnd-Frontend
 */
tinymce.PluginManager.add('wndimage', function(editor, url) {
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
					html: '<div style="text-align:center;"><img id="wnd-single-img" src=""></div>'
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
				text: 'Save',
				primary: true
			}],
			img: {},
			// 提交
			onSubmit: function(api) {
				var data = api.getData();
				if (data.title) {
					img.alt = data.title;
				}
				img.removeAttribute('id');
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

				let config = editor.getParam('wnd_config');
				let data = api.getData();

				// 上传文件
				let file = data.fileinput[0];
				if (config.oss_sign_nonce) {
					let file_info = await upload_to_oss(file, config.oss_sign_nonce, config.oss_sign_endpoint);
					img.src = file_info.url;
					img.dataset.id = file_info.id;
				} else {
					let formdata = new FormData();
					formdata.append('wnd_file[]', file);
					formdata.append('post_parent', config.post_parent);
					formdata.append('_ajax_nonce', config.upload_nonce);

					let file_info = await upload_to_local_server(formdata, config.upload_url);
					img.src = file_info.url;
					img.dataset.id = file_info.id;
				}
			},
		});

		// Ajax
		async function upload_to_local_server(form_data, upload_url) {
			let file_info = axios({
				url: upload_url,
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

		// 浏览器直传 OSS
		async function upload_to_oss(file, oss_sign_nonce, oss_sign_endpoint) {
			let extension = file.name.split('.').pop();
			let token = await get_oss_token(extension, oss_sign_nonce, oss_sign_endpoint);
			let file_info = axios({
				url: token.url,
				method: 'PUT',
				data: file,
				headers: token.headers,
				/**
				 *  Access-Control-Allow-Origin 的值为通配符 ("*") ，而这与使用credentials相悖。
				 * @link https://developer.mozilla.org/zh-CN/docs/Web/HTTP/CORS/Errors/CORSNotSupportingCredentials
				 **/
				withCredentials: false,
			}).then(res => {
				if (res.status == 200) {
					return token;
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
		function get_oss_token(extension, oss_sign_nonce, oss_sign_endpoint) {
			let token = axios({
				url: oss_sign_endpoint,
				method: 'POST',
				data: {
					'_ajax_nonce': oss_sign_nonce,
					'extension': extension,
				},
			}).then(res => {
				return res.data.data;
			})

			return token;
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