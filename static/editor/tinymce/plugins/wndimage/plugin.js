/**
 * @link https://www.tiny.cloud/docs/ui-components/dialog/#dialogdataandstate
 **/
tinymce.PluginManager.add('wndimage', function(editor, url) {
	var openDialog = function() {
		return editor.windowManager.open({
			title: "",
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
			onChange: function(api, details) {
				// 非文件字段变动
				if ('fileinput' !== details.name) {

					return false;
				}

				// 获取节点
				img = document.querySelector("#wnd-single-img");

				let config = editor.getParam('wnd_config');
				let data = api.getData();

				//获取到选中的文件，创建formdata对象
				let file = data.fileinput[0];
				let formdata = new FormData();
				formdata.append("wnd_file[]", file);
				formdata.append("post_parent", config.post_parent);
				formdata.append("_ajax_nonce", config.upload_nonce);

				//创建xhr，使用ajax进行文件上传
				let xhr = new XMLHttpRequest();
				xhr.open("POST", config.upload_url);
				xhr.setRequestHeader('X-WP-Nonce', config.rest_nonce);

				//回调
				xhr.onreadystatechange = function() {
					if (xhr.readyState == 4 && xhr.status == 200) {
						res = JSON.parse(xhr.responseText);
						imgurl = res.data[0].url;
						img.src = imgurl;
						img.dataset.id = res.data[0].id;
					}
				}

				//获取上传的进度
				xhr.upload.onprogress = function(event) {
					if (event.lengthComputable) {
						var percent = event.loaded / event.total * 100;
						console.log(percent);
					}
				}

				//将formdata上传
				xhr.send(formdata);
			},
		});
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
				name: "Wnd Image", //插件名称
				url: "https://wndwp.com", //作者网址
			};
		}
	};
});