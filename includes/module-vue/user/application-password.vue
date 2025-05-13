<div id="application-password">
	<div class="field has-addons">
		<div class="control is-expanded">
			<input class="input" type="text" placeholder="秘钥名称" v-model="name">
		</div>
		<div class="control">
			<button class="button is-info" @click="create_password()">创建</button>
		</div>
	</div>
	<div class="field">
		<p class="help">请妥善保管本秘钥，关闭后将无法重现</p>
		<input type="text" class="input" readonly v-model="new_password">
	</div>
	<div id="lists">
		<table class="table is-striped is-fullwidth is-striped">
			<th class="is-narrow">name</th>
			<th>created</th>
			<th>last_used</th>
			<th>last_ip</th>
			<th class="is-narrow">delete</th>
			<tr v-for="(item, index) in passwords" :key="item.uuid" style="border-top: 1px dotted #CCC;">
				<td>{{item.name}}</td>
				<td>{{get_date(item.created)}}
				<td>{{item.last_used ? get_date(item.last_used) : ''}}
				<td>{{item.last_ip}}</td>
				<td><button class="button is-small" @click="delete_password(item.uuid, index)">删除</button></td>
			</tr>
		</table>
	</div>
</div>
<script>
	{
		const parent = document.querySelector("#application-password").parentNode;
		const option = {
			data() {
				return {
					passwords: module_data.passwords,
					name: "",
					new_password: "",
				}
			},

			methods: {
				create_password: async function () {
					let res = await wnd_ajax_action("user/wnd_create_application_password", { "name": this.name });
					this.new_password = res.data[0];
					this.passwords.push(res.data[1]);
				},
				delete_password: async function (uuid, index) {
					if (!confirm("确认删除本条秘钥？")) {
						return;
					}

					let res = await wnd_ajax_action("user/wnd_delete_application_password", { "uuid": uuid });
					this.passwords.splice(index, 1);
				},
				get_date: function (timestamp) {
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
				},
			},
			updated() {
				funTransitionHeight(parent, trs_time);
			}
		}

		const app = Vue.createApp(option);
		app.mount('#application-password');
		vueInstances.push(app);
	}
</script>