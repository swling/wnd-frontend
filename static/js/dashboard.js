
/**
 *@since 0.9.88
 *通过参数更新 url hash
 */
function wnd_update_url_hash(obj, ignore = []) {
	// 解析当前 hash（去掉 #，转为 URLSearchParams）
	const currentParams = new URLSearchParams(location.hash.slice(1));

	// 将 obj 的 key-value 合并进去
	for (const key in obj) {
		if (ignore.length && ignore.includes(key)) {
			continue;
		}

		if (obj[key] === null || obj[key] === undefined) {
			currentParams.delete(key); // 可选逻辑：允许通过 null/undefined 删除字段
		} else {
			currentParams.set(key, obj[key]);
		}
	}

	// 构建新的 hash 字符串
	window.location.hash = decodeURIComponent(currentParams.toString());
}

function wnd_time_to_date(timestamp) {
	// 创建一个新的Date对象，使用时间戳作为参数（以毫秒为单位） 
	const date = new Date(timestamp * 1000);
	const year = date.getFullYear();
	const month = (date.getMonth() + 1).toString().padStart(2, '0');
	const day = date.getDate().toString().padStart(2, '0');
	const hours = date.getHours().toString().padStart(2, '0');
	const minutes = date.getMinutes().toString().padStart(2, '0');
	const seconds = date.getSeconds().toString().padStart(2, '0');

	let now = new Date();
	if (date.getFullYear() === now.getFullYear()) {
		return `${month}-${day} ${hours}:${minutes}:${seconds}`;
	} else {
		return `${year}-${month}-${day} ${hours}:${minutes}:${seconds}`;
	}
}
function wnd_time_to_string(timestamp) {
	let date = new Date(timestamp * 1000),
		now = new Date(), diff = now - date, minutes = Math.floor(diff / 60000), hours = Math.floor(minutes / 60), days = Math.floor(hours / 24);

	if (date.getFullYear() === now.getFullYear() && date.getMonth() === now.getMonth() && date.getDate() === now.getDate()) {
		if (hours === 0) {
			return minutes + " minutes ago";
		} else if (hours < 24) {
			return hours + " hours ago";
		} else {
			return date.toDateString();
		}
	} else {
		return wnd_time_to_date(timestamp);
	}
}