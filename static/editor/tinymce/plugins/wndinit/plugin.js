/**
 * @link https://www.tiny.cloud/docs/ui-components/dialog/#dialogdataandstate
 **/
tinymce.PluginManager.add('wndinit', function(editor, url) {
	//  @link https://www.tiny.cloud/docs/ui-components/contexttoolbar/
	editor.ui.registry.addContextToolbar('imagealignment', {
		predicate: function(node) {
			return node.nodeName.toLowerCase() === 'img'
		},
		items: 'alignleft aligncenter alignright image',
		position: 'node',
		scope: 'node'
	});

	editor.ui.registry.addContextToolbar('textselection', {
		predicate: function(node) {
			let nodeName = node.nodeName.toLowerCase();
			return (!editor.selection.isCollapsed() && !['img', 'pre', 'code', 'body'].includes(nodeName));
		},
		items: 'styles bold italic underline strikethrough | forecolor backcolor | blockquote link unlink',
		position: 'selection',
		scope: 'node'
	});

	return {
		getMetadata: function() {
			return {
				//插件名和链接会显示在“帮助”→“插件”→“已安装的插件”中
				name: "Wnd Init", //插件名称
				url: "https://wndwp.com", //作者网址
			};
		}
	};
});