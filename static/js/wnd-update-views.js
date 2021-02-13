/**
 * 流量统计
 */
function _wnd_update_views(post_id, interval) {
    if (wnd_is_spider()) {
        return;
    }

    var timestamp = Date.parse(new Date()) / 1000;
    var wnd_views = localStorage.getItem('wnd_views') ? JSON.parse(localStorage.getItem('wnd_views')) : [];
    var max_length = 10;
    var is_new = true;

    // 数据处理
    for (var i = 0; i < wnd_views.length; i++) {
        if (wnd_views[i].post_id == post_id) {
            // 存在记录中：且时间过期
            if (wnd_views[i].timestamp < timestamp - interval) {
                wnd_views[i].timestamp = timestamp;
                var is_new = true;
            } else {
                var is_new = false;
            }
            break;
        }
    }

    // 新浏览
    if (is_new) {
        var new_view = {
            'post_id': post_id,
            'timestamp': timestamp
        };
        wnd_views.unshift(new_view);
    }

    // 删除超过长度的元素
    if (wnd_views.length > max_length) {
        wnd_views.length = max_length;
    }

    // 更新服务器数据
    data = new FormData();
    data.append('post_id', post_id);
    if (is_new) {
        axios({
            url: wnd_endpoint_api + "/wnd_update_views" + lang_query,
            method: 'POST',
            data: data,
        }).then(function(response) {
            if (1 == response.data.status) {
                localStorage.setItem("wnd_views", JSON.stringify(wnd_views));
            }
        });
    }
}