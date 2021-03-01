/**
 *敬请留意：
 *下列某些选择器可能随wordpress wp_list_comments() 及 comment_form() 变化而失效
 */
document.addEventListener('submit', function(e) {
	let form = e.target;
	if ('commentform' != form.id) {
		return;
	}
	e.preventDefault();

	// comments List
	let list_class = 'comment-list';
	let list = document.querySelector('.' + list_class);

	// Respond Form
	let respond = form.closest('#respond');
	let parent = form.querySelector('#comment_parent').value;
	let button = form.querySelector("[type='submit']");
	let form_data = new FormData(form);

	// 弹窗提示信息
	function createButterbar(message, time) {
		wnd_alert_msg(`<div class="has-text-centered"><p class="ajax-comment-message">${message}</p></div>`, time);
	}

	// 定义完成，提交行为开始
	createButterbar(wnd.msg.waiting + '....');
	button.disabled = true;
	axios({
		'method': form.getAttribute("method"),
		url: wnd.rest_url + wnd.comment.api,
		data: form_data,
	}).then(response => {
		if (0 == response.data.status) {
			createButterbar(response.data.msg);
			button.disabled = false;
			return;
		}

		// 动态插入评论。列表顺序：wnd.comment.order, 表单位置：wnd.comment.form_pos
		if (parent != 0) {
			respond.insertAdjacentHTML('beforebegin', `<ul class="children">${response.data.data}</ul>`);
		} else if (list) {
			let position = ('desc' == wnd.comment.order) ? 'afterbegin' : 'beforeend';
			list.insertAdjacentHTML(position, response.data.data);
		} else {
			let position = ('top' == wnd.comment.form_pos) ? 'beforeend' : 'beforebegin';
			respond.insertAdjacentHTML(position, `<ol class="${list_class}">${response.data.data}</ol>`);
		}

		// 后续
		createButterbar(wnd.msg.submit_successfully, 1);
		form.querySelector('textarea').value = '';
		button.disabled = false;
		reply_link = respond.querySelector('#cancel-comment-reply-link');
		if (reply_link) {
			reply_link.click();
		}
	}).catch(error => {
		console.log(error);
		button.disabled = false;
	});
});