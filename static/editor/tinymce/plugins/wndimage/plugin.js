/**
 * @link https://www.tiny.cloud/docs/ui-components/dialog/#dialogdataandstate
 * 这不是一个标准的 Tinymce 插件
 * 仅适用于 WordPress 插件：Wnd-Frontend
 */
tinymce.PluginManager.add('wndimage', function (editor, url) {
	let config = editor.getParam('wnd_config');
	let openDialog = function () {
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
				},]
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
			onSubmit: function (api) {
				let data = api.getData();
				if (data.title) {
					img.alt = data.title;
				}
				img.removeAttribute('id');
				img.removeAttribute('style');
				editor.insertContent(img.outerHTML);
				api.close();
			},
			onChange: async function (api, details) {
				// 非文件字段变动
				if ('fileinput' !== details.name) {
					return false;
				}

				// 获取节点
				img = document.querySelector('#wnd-single-img');

				let data = api.getData();

				// 上传文件
				let file = data.fileinput[0];
				const { width, height } = await getImageSizeFromFile(file);

				if (config.oss_direct_upload) {
					let sign_data = {
						'post_parent': config.post_parent,
					};
					let file_info = await wnd_upload_to_oss(file, sign_data);
					if (file_info) {
						img.src = file_info.url;
						img.dataset.id = file_info.id;
						img.width = width;
						img.height = height;
						img.loading = 'lazy';
					}
				} else {
					let formdata = new FormData();
					formdata.append('wnd_file', file);
					formdata.append('post_parent', config.post_parent);

					let file_info = await upload_to_local_server(formdata);
					if (file_info) {
						img.src = file_info.url;
						img.dataset.id = file_info.id;
						img.width = width;
						img.height = height;
						img.loading = 'lazy';
					}
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
					alert(res.data.msg);
					return '';
				} else {
					return res.data.data;
				}
			}).catch(err => {
				console.log(err);
			});

			return file_info;
		}
	};

	async function getImageSizeFromFile(file) {
		return new Promise((resolve, reject) => {
			const reader = new FileReader();
			reader.onload = function (e) {
				const img = new Image();
				img.onload = function () {
					resolve({ width: img.width, height: img.height });
				};
				img.onerror = reject;
				img.src = e.target.result;
			};
			reader.onerror = reject;
			reader.readAsDataURL(file);
		});
	}

	// 注册一个工具栏按钮名称
	editor.ui.registry.addButton('wndimage', {
		icon: 'image',
		onAction: function () {
			openDialog();
		}
	});

	// 注册一个菜单项名称 menu/menubar
	editor.ui.registry.addMenuItem('wndimage', {
		text: 'Wnd Image',
		onAction: function () {
			openDialog();
		}
	});

	return {
		getMetadata: function () {
			return {
				//插件名和链接会显示在“帮助”→“插件”→“已安装的插件”中
				name: 'Wnd Image', //插件名称
				url: 'https://wndwp.com', //作者网址
			};
		}
	};
});