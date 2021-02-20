/**
 *@since 0.9.25
 *前端用户中心
 */
function user_center_hash() {
	let hash = location.hash;
	if (!hash) {
		wnd_ajax_embed('#ajax-module', 'wnd_user_overview');
		return;
	}

	var module = hash.replace('#', '')
	wnd_ajax_embed('#ajax-module', module);
}

// 根据 hash 加载 Module
window.onload = function() {
	user_center_hash();
	window.onhashchange = user_center_hash;
}