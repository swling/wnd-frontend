
// 定义全局组件 @link https://cn.vuejs.org/guide/components/v-model.html
// ********************************* 富文本编辑器组件
const RichEditor = {
    props: ['modelValue', 'post_id', 'parent_node'],
    emits: ['update:modelValue'],
    template: `<textarea id="rich-editor" class="is-hidden" v-model="modelValue"></textarea>`,
    data() {
        return {
            editor: null,
        };
    },
    methods: {
        // 组件方法
        // 富文本编辑器 @link https://tiny.cloud
        build_editor: function (selector) {
            // 监听编辑器所在元素高度变化
            const resizeObserver = new ResizeObserver(entries => {
                funTransitionHeight(this.parent_node, trs_time);
            });
            const container = document.querySelector(selector);
            if (container) {
                resizeObserver.observe(container);
            }

            // 销毁可能存在的编辑器
            // tinymce.remove(selector);
            if (tinymce.activeEditor) {
                tinymce.activeEditor.destroy();
            }

            tinymce.init({
                // 基础配置
                branding: false,
                selector: selector,
                menubar: false,
                width: '100%',  // 设置为百分比
                language: wnd.lang,
                cache_suffix: cache_suffix,

                // 自动保存
                autosave_restore_when_empty: true,
                autosave_prefix: 'tinymce-autosave-' + this.post_id,
                autosave_ask_before_unload: false,

                // 设置文件 URL 为绝对路径
                relative_urls: false,
                remove_script_host: false,

                // 将分页符设置为 WP More
                pagebreak_separator: '<!--more-->',
                pagebreak_split_block: true,

                // 定义插件及菜单按钮
                plugins: 'advlist autolink autoresize autosave code codesample fullscreen image link lists pagebreak wordcount wndimage wndinit',
                toolbar: 'formatselect | alignleft aligncenter alignright bullist numlist | ' +
                    'blockquote wndimage link codesample  pagebreak wndpaidcontent | removeformat code fullscreen',

                // 自定义配置
                wnd_config: {
                    'rest_nonce': wnd.rest_nonce,
                    'upload_url': wnd.rest_url + 'action/common/wnd_upload_file',
                    'post_parent': this.post_id,
                    'oss_direct_upload': wnd.oss_direct_upload,
                },
                setup: (editor) => {
                    this.editor = editor;
                    texarea = container;
                    texarea.style.removeProperty('display');
                    // editor.on('init', () => {
                    //     editor.setContent(this.modelValue || '');
                    // });
                    editor.on('change', () => {
                        // this.content = editor.getContent();
                        this.parent_node.style.removeProperty('height');
                        this.$emit('update:modelValue', editor.getContent());
                    });
                    editor.on('keyup', () => {
                        this.$emit('update:modelValue', editor.getContent()); // 监听键盘输入
                    });
                },
                init_instance_callback: (editor) => {
                    resizeObserver.observe(this.parent_node.querySelector(`.tox-tinymce`));
                },
            });
        },
    },
    watch: {
        modelValue: function (newVal) {
            if (this.editor && this.editor.getContent() !== newVal) {
                this.editor.setContent(newVal);
            }
        },
    },
    mounted: async function () {
        if ('undefined' == typeof tinymce) {
            let url = static_path + 'editor/tinymce/tinymce.min.js' + cache_suffix;
            await wnd_load_script(url);
        }
        this.build_editor("#rich-editor");
    },

};

// ********************************* 下拉组件
const DropdownSearch = {
    name: 'DropdownSearch',
    props: {
        options: {
            type: Array,
            required: true
        },
        modelValue: {
            type: [String, Number],
            default: null
        },
        required: {
            type: Boolean,
            default: false
        }
    },
    emits: ['update:modelValue', 'select'],
    data() {
        return {
            search: '',
            isOpen: false,
            activeIndex: -1,
            selectedOption: null
        };
    },
    computed: {
        filteredOptions() {
            return this.options.filter(opt =>
                opt.name.toLowerCase().includes(this.search.toLowerCase())
            );
        },
        maybe_required() {
            return this.required ? (this.options.length > 0) : false;
        }
    },
    mounted() {
        this.injectStyles();
        this.updateSelection();
    },
    methods: {
        // 核心方法：统一更新选中状态
        updateSelection() {
            if (this.modelValue != null) {
                const match = this.options.find(o => o.value === this.modelValue);
                this.selectedOption = match || null;
                this.search = match ? match.name : '';
            } else {
                this.selectedOption = null;
                this.search = '';
            }
        },

        // 选项选中逻辑
        selectOption(opt) {
            this.search = opt.name;
            this.selectedOption = opt;
            this.isOpen = false;
            this.$emit('select', opt);
            this.$emit('update:modelValue', opt.value);
        },

        // 键盘导航处理
        handleKeydown(e) {
            const filtered = this.filteredOptions;
            if (!this.isOpen && (e.key === 'ArrowDown' || e.key === 'ArrowUp')) {
                this.isOpen = true;
                return;
            }
            switch (e.key) {
                case 'ArrowDown':
                    this.activeIndex = (this.activeIndex + 1) % filtered.length;
                    break;
                case 'ArrowUp':
                    this.activeIndex = (this.activeIndex - 1 + filtered.length) % filtered.length;
                    break;
                case 'Enter':
                    e.preventDefault();
                    if (this.activeIndex >= 0 && this.activeIndex < filtered.length) {
                        this.selectOption(filtered[this.activeIndex]);
                    }
                    break;
            }
        },

        // 输入框失焦处理（优化版）
        handleBlur() {
            this.$nextTick(() => {
                this.search = this.selectedOption?.name || '';
                this.isOpen = false;
            });
        },

        // 输入框点击处理
        handleClickOnInput(e) {
            this.isOpen = true;
            // 如果已有选中项，清空搜索内容以展示更多选项
            if (this.selectedOption) {
                this.search = '';
            }
            this.$nextTick(() => this.$refs.input.focus());
        },
        injectStyles() {
            const style = document.createElement('style');
            style.textContent = `
                .dropdown-search .is-active-option {
                    cursor: pointer;
                    background-color: #F0F0F0;
                    transition: background-color 0.05s;
                }`;
            document.head.appendChild(style);
        }
    },
    watch: {
        modelValue: {
            immediate: true,
            handler(val) {
                this.updateSelection();
            }
        },
        options: {
            immediate: true,
            handler() {
                this.updateSelection();
            }
        }
    },
    template: `
<div class="dropdown dropdown-search is-active column is-paddingless">
  <div class="dropdown-trigger" style="width: 100%;">
    <input
      ref="input"
      :class="[{'selected': selectedOption }, 'input']"
      type="text"
      placeholder="..."
      v-model="search"
      :disabled="options.length < 1"
      @mousedown="handleClickOnInput"
      @input="isOpen = true"
      @keydown="handleKeydown"
      @blur="handleBlur"
      :required="maybe_required"
    />
  </div>
  <div class="dropdown-menu" v-show="isOpen" style="width: 100%;">
    <div class="dropdown-content" style="overflow-y:auto;max-height:300px;z-index:99">
      <a
        v-for="(opt, index) in filteredOptions"
        :key="opt.value"
        class="dropdown-item"
        :class="[{'is-active-option': index === activeIndex }, {'is-selected-option': modelValue === opt.value}]"
        @mousedown.prevent="selectOption(opt)"
      >
      {{ opt.name }}
      </a>
    </div>
  </div>
</div>
    `
};

const MultiLevelDropdown = {
    components: { DropdownSearch },
    props: {
        options: Array,
        modelValue: Array,
        required: Boolean
    },
    data() {
        return {
            selectedValues: [...this.modelValue],
            currentOptionsList: []
        };
    },
    watch: {
        modelValue: {
            immediate: true,
            handler(val) {
                const isSame = JSON.stringify(val) === JSON.stringify(this.selectedValues);
                if (Array.isArray(val) && !isSame) {
                    this.initializeFromModelValue(val);
                }
            }
        }
    },
    methods: {
        initializeFromModelValue(val) {
            this.selectedValues = [];
            this.currentOptionsList = [];
            let opts = this.options;
            for (const value of val) {
                this.currentOptionsList.push(opts);
                const selected = opts.find(o => o.value === value);
                if (selected) {
                    this.selectedValues.push(selected.value);
                    opts = selected.children || [];
                } else {
                    break;
                }
            }
            if (opts?.length) {
                this.currentOptionsList.push(opts);
            }
        },
        handleSelect(level, option) {
            this.selectedValues.splice(level);
            this.selectedValues[level] = option.value;
            this.currentOptionsList.splice(level + 1);
            if (option.children?.length) {
                this.currentOptionsList.push(option.children);
            }
            this.$emit('update:modelValue', [...this.selectedValues]);
        }
    },
    mounted() {
        this.initializeFromModelValue(this.modelValue);
    },
    template: `
  <div class="dropdown-container columns is-gapless" style="width:100%">
    <dropdown-search
      v-for="(opts, index) in currentOptionsList"
      :key="index"
      :options="opts"
      :model-value="selectedValues[index] ?? null"
      @update:modelValue="val => selectedValues[index] = val"
      @select="opt => handleSelect(index, opt)"
      :required="required"
    />
  </div>
`
};
// ********************************* Taginput
const TagsInput = {
    props: {
        options: Array,
        maxTags: {
            type: Number,
            default: Infinity
        },
        modelValue: {
            type: Array,
            default: () => []
        },
        required: {
            type: Boolean,
            default: false
        }
    },
    emits: ['update:modelValue'],
    data() {
        return {
            input: '',
            selectedIndex: -1,
            selectedTags: [],
            filteredOptions: [],
            pendingDeleteIndex: null
        };
    },
    mounted() {
        this.selectedTags = this.mapModelValueToTags(this.modelValue);
        this.filterOptions();
        this.injectStyles();
    },
    watch: {
        input() {
            this.filterOptions();
            this.pendingDeleteIndex = null;
        },
        modelValue(newVal) {
            this.selectedTags = this.mapModelValueToTags(newVal);
            this.filterOptions();
        },
    },
    methods: {
        // 新增映射方法
        mapModelValueToTags(modelValue) {
            return modelValue.map(val => {
                const found = this.options.find(opt => opt.value === val);
                return found || { name: val, value: val };
            });
        },
        filterOptions() {
            const term = this.input.toLowerCase();
            const selectedValues = this.selectedTags.map(tag => tag.value);
            this.filteredOptions = this.options.filter(
                opt => opt.name.toLowerCase().includes(term) && !selectedValues.includes(opt.value)
            );
            this.selectedIndex = -1;
        },
        addTag(tag) {
            if (this.selectedTags.length >= this.maxTags) return;
            if (this.selectedTags.some(t => t.value === tag.value)) return;
            this.selectedTags.push(tag);
            this.input = '';
            this.pendingDeleteIndex = null;
            // this.filterOptions();
            this.$emit('update:modelValue', this.selectedTags.map(t => t.value));
        },
        addFromInput() {
            const term = this.input.trim();
            if (!term) return;
            const existing = this.options.find(opt => opt.name.toLowerCase() === term.toLowerCase());
            const newTag = existing || { name: term, value: term };
            this.addTag(newTag);
        },
        removeTag(index) {
            if (index >= 0) {
                this.selectedTags.splice(index, 1);
                this.pendingDeleteIndex = null;
                // this.filterOptions();
                this.$emit('update:modelValue', this.selectedTags.map(t => t.value));
            }
        },
        handleKeydown(e) {
            const len = this.filteredOptions.length;

            if (e.key === 'ArrowDown') {
                if (len > 0) {
                    this.selectedIndex = (this.selectedIndex + 1) % len;
                }
            } else if (e.key === 'ArrowUp') {
                if (len > 0) {
                    this.selectedIndex = (this.selectedIndex - 1 + len) % len;
                }
            } else if (e.key === 'Enter') {
                e.preventDefault();

                const term = this.input.trim();
                if (!term) return;

                const tag = this.filteredOptions[this.selectedIndex];
                if (tag) {
                    this.addTag(tag);
                } else {
                    this.addFromInput();
                }
            } else if (e.key === 'Backspace' && this.input === '') {
                if (this.pendingDeleteIndex === this.selectedTags.length - 1) {
                    this.removeTag(this.pendingDeleteIndex);
                } else if (this.selectedTags.length > 0) {
                    this.pendingDeleteIndex = this.selectedTags.length - 1;
                }
            }
        },
        onFocus() {
            this.filterOptions();
        },
        handleBlur() {
            this.filteredOptions = [];
            this.selectedIndex = -1;
            this.pendingDeleteIndex = null;
        },
        isPendingDelete(index) {
            return index === this.pendingDeleteIndex;
        },
        injectStyles() {
            const style = document.createElement('style');
            style.textContent = `
.tags-input {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 0.5em;
  border-bottom: 1px solid #dbdbdb;
  padding: 0.5em 0 0 0;
  min-height: 2.5em;
}
.tags-input input.input {
  border: none;
  box-shadow: none;
  flex: 1;
  min-width: 120px;
  padding: 0.25em 0.5em;
}
.tags-input input.input:focus {
  outline: none;
  box-shadow: none;
}
.tags-input .tag {
  margin-bottom: 0;
  display: flex;
  align-items: center;
}
.tags-input-warp .dropdown-item:hover, .tags-input-warp .dropdown-item.is-active {
  cursor: pointer;
  transition: background-color 0.05s;
  background-color: #f0f0f0;
}
      `;
            document.head.appendChild(style);
        }
    },
    template: `
<div class="tags-input-warp field dropdown is-active" style="width:100%">
  <div class="control tags-input" style="width:100%">
    <span
      v-for="(tag, index) in selectedTags"
      :key="tag.value"
      class="tag"
      :class="['is-medium', isPendingDelete(index) ? 'is-danger' : 'is-danger is-light']"
    >
      {{ tag.name }}
      <a class="delete is-small" @click="removeTag(index)"></a>
    </span>
    <input
      class="input"
      type="text"
      v-model="input"
      @keydown="handleKeydown"
      :placeholder="selectedTags.length >= maxTags ? 'Maximum reached' : 'Add a tag...'"
      :disabled="selectedTags.length >= maxTags"
      @focus="onFocus"
      @blur="handleBlur"
      :required="required"
    />
  </div>
  <div v-show="filteredOptions.length && input && selectedTags.length < maxTags" class="dropdown-menu" style="width: 100%;">
    <ul class="dropdown-content is-marginless">
      <li
        v-for="(option, index) in filteredOptions"
        :key="option.value"
        :class="['dropdown-item', { 'is-active': index === selectedIndex }]"
        @mousedown.prevent="addTag(option)"
      >
        {{ option.name }}
      </li>
    </ul>
  </div>
</div>
  `
};
// ********************************* 缩略图组件
const ThumbnailCard = {
    props: {
        imgWidth: {
            type: Number,
            default: 100 // 展示尺寸
        },
        imgHeight: {
            type: Number,
            default: 100
        },
        cropWidth: {
            type: Number,
            default: 300 // 裁剪输出尺寸
        },
        cropHeight: {
            type: Number,
            default: 300
        },
        meta_key: {
            type: String,
            default: ''
        },
        post_parent: {
            type: Number,
            default: 0
        },
        thumbnail: {
            type: String,
            default: ''
        }
    },
    data() {
        return {
            imageDataUrl: '',
            tempImageUrl: '',
            cropper: null,
            showModal: false
        };
    },
    mounted() {
        this.injectStyles();
    },
    methods: {
        triggerFileInput() {
            this.$refs.fileInput.click();
        },
        handleFileChange(event) {
            const file = event.target.files[0];
            if (file && file.type.startsWith('image/')) {
                const reader = new FileReader();
                reader.onload = async (e) => {
                    // 如果 Cropper.js 没有加载，则加载它
                    if (typeof Cropper === 'undefined') {
                        await wnd_load_script(static_path + 'cropper/cropper.min.js' + cache_suffix);
                        await wnd_load_style(static_path + 'cropper/cropper.min.css' + cache_suffix);
                    }
                    this.tempImageUrl = e.target.result;
                    this.showModal = true;
                    this.$nextTick(() => {
                        const image = this.$refs.cropImage;
                        this.cropper = new Cropper(image, {
                            aspectRatio: this.imgWidth / this.imgHeight,
                            viewMode: 1,
                            autoCropArea: 1,
                        });
                    });
                };
                reader.readAsDataURL(file);
            }
        },
        applyCrop() {
            if (this.cropper) {
                const canvas = this.cropper.getCroppedCanvas({
                    width: this.cropWidth,
                    height: this.cropHeight
                });

                canvas.toBlob(blob => {
                    this.uploadCroppedImage(blob, canvas);  // 调用上传方法
                }, 'image/webp');

                this.cropper.destroy();
                this.cropper = null;
                this.showModal = false;
            }
        },
        cancelCrop() {
            if (this.cropper) {
                this.cropper.destroy();
                this.cropper = null;
            }
            this.showModal = false;
        },
        async uploadCroppedImage(blob, canvas) {
            try {
                if (wnd.oss_direct_upload) {
                    let response = await this.upload_to_oss(blob);
                    if (response.id) {
                        this.imageDataUrl = URL.createObjectURL(blob);
                    } else {
                        alert('Upload failed : ' + response.msg)
                    }
                } else {
                    let response = await this.upload_to_local_server(blob);
                    if (response.status > 0) {
                        this.imageDataUrl = URL.createObjectURL(blob);
                    } else {
                        alert(response.msg);
                    }
                }
                // this.thumbnail_url = response.data[0].thumbnail;
            } catch (error) {
                console.error(error);
                alert('Upload failed.');
            }
        },
        async upload_to_local_server(blob) {
            const formData = new FormData();
            formData.append('wnd_file[]', blob, 'CroppedImage.webp');
            formData.append('meta_key', this.meta_key);
            formData.append('post_parent', this.post_parent);
            let response = await wnd_ajax_action("common/wnd_upload_file", formData);
            return response;
        },
        // 浏览器直传 OSS
        async upload_to_oss(blob) {
            let file = new File([blob], 'oss_file.webp', {
                type: 'image/webp'
            });

            let upload_res = await wnd_upload_to_oss(file, { "meta_key": this.meta_key, "post_parent": this.post_parent });
            return upload_res;
        },
        injectStyles() {
            const style = document.createElement('style');
            style.textContent = `
.thumbnail-img {
    object-fit: cover;
    display: block;
    cursor: pointer;
}
.thumbnail-img:hover {
    border-color: #3273dc;
}
.thumbnail-drop-area {
    border: 1px dashed #dbdbdb;
    border-radius: 4px;
    position: relative;
    cursor: pointer;
}
.cropper-container {
    max-height: 600px;
    max-width: 800px;
    overflow: hidden;
}
      `;
            document.head.appendChild(style);
        }
    },
    computed: {
        thumbnail_url() {
            return this.imageDataUrl || this.thumbnail;
        }
    },
    template: `
<div class="thumbnail-card" style="width: 100%;">
      <div class="thumbnail-drop-area" :style="{ width: imgWidth + 'px', height: imgHeight + 'px' }" @click="triggerFileInput">
        <img v-if="thumbnail_url" :src="thumbnail_url" class="thumbnail-img" :width="imgWidth" :height="imgHeight"/>
        <input type="file" ref="fileInput" accept="image/*" @change="handleFileChange" style="display: none">
      </div>
  </div>
  <div class="modal" :class="{ 'is-active': showModal }">
    <div class="modal-background" @click="cancelCrop"></div>
    <div class="modal-card" style="width: auto; max-width: 90%;">
      <section class="modal-card-body has-text-centered">
        <i>双击裁剪 / Double-click to crop</i>
        <div class="cropper-container" @dblclick="applyCrop">
          <img :src="tempImageUrl" ref="cropImage" style="max-width: 100%;"/>
        </div>
      </section>
    </div>
  </div>
</div>
    `
};
// ********************************* 文件上传组件
const FileUploader = {
    props: {
        post_parent: {
            type: Number,
            default: 0
        },
        meta_key: {
            type: String,
            default: 0
        },
        file_id: {
            type: Number,
            default: 0
        },
        file_url: {
            type: String,
            default: ''
        },
        is_paid: {
            type: Boolean,
            default: false
        }
    },
    // props 不能直接修复，因此创建副本
    data() {
        return {
            current_file_id: this.file_id,
            current_file_url: this.file_url,
        };
    },
    // 解决父组件数据异步加载的问题
    watch: {
        file_id(newVal) {
            this.current_file_id = newVal;
        },
        file_url(newVal) {
            this.current_file_url = newVal;
        }
    },
    methods: {
        async handleFileChange(event) {
            const file = event.target.files[0];
            try {
                if (wnd.oss_direct_upload) {
                    let response = await this.upload_to_oss(file);
                    if (response.id) {
                        this.current_file_id = response.id;
                        this.current_file_url = response.url;
                    } else {
                        alert('Upload failed : ' + response.msg)
                    }
                } else {
                    let response = await this.upload_to_local_server(file);
                    if (response.status > 0) {
                        this.current_file_id = response.data[0].id;
                        this.current_file_url = response.data[0].url;
                    } else {
                        alert(response.msg);
                    }
                }
            } catch (error) {
                console.error(error);
                alert('Upload failed.');
            }
        },
        async upload_to_local_server(file) {
            const formData = new FormData();
            formData.append('wnd_file[]', file);
            formData.append('meta_key', this.meta_key);
            formData.append('post_parent', this.post_parent);
            formData.append('is_paid', this.is_paid);
            let response = await wnd_ajax_action("common/wnd_upload_file", formData);
            return response;
        },
        // 浏览器直传 OSS
        async upload_to_oss(file) {
            let upload_res = await wnd_upload_to_oss(file,
                {
                    "meta_key": this.meta_key,
                    "post_parent": this.post_parent,
                    "is_paid": this.is_paid
                }
            );
            return upload_res;
        },
        // 删除文件
        delete_file: async function () {
            data = {
                'file_id': this.current_file_id,
                'meta_key': this.meta_key,
                'post_parent': this.post_parent,
            };

            let response = await wnd_ajax_action("common/wnd_delete_file", data);
            if (response.status > 0) {
                this.current_file_id = 0;
                this.current_file_url = '';
            } else {
                alert(response.msg);
            }
        },
    },
    template: `
<div class="file has-name is-fullwidth is-normal">
	<div class="file-label" style="cursor: default;">
		<input type="file" id="paid-file-component" class="file file-input" @change="handleFileChange"/>
		<label class="file-cta is-clickable" for="paid-file-component">
            <span class="file-icon"><i class="fa fa-upload"></i></span>
        </label>
		<span class="file-name">
			<a v-if="current_file_url" :href="current_file_url" target="_blank">
				<i class="fas fa-download"></i> ……
			</a>
			<span v-else>……</span>
            <span v-show="current_file_id" class="is-pulled-right">
                <a class="delete is-medium" @click="delete_file()" title="Delete"></a>
            </span>
		</span>
	</div>
</div>
    `
};
// ********************************* 价格设置字段
const PostPriceInput = {
    props: {
        post_parent: {
            type: Number,
            default: 0
        },
        modelValue: {
            type: [Number, String],  // 允许数字和字符串类型
            default: ""
        }
    },
    emits: ['update:modelValue'],
    methods: {
        set_sku() {
            wnd_ajax_modal('common/wnd_sku_form', { 'post_id': this.post_parent })
        }
    },
    template: `
<div class="field has-addons">
	<div class="control is-expanded has-icons-left">
		<input 
            placeholder="price" 
            min="0" 
            step="0.01" 
            type="number" 
            class="input" 
            :value="modelValue" 
            @input="$emit('update:modelValue', $event.target.value)" 
        />
		<span class="icon is-left"><i class="fas fa-dollar-sign"></i></span>
	</div>
	<div class="control">
		<a class="button" @click="set_sku">SKU</a>
	</div>
</div>
    `
};
// ********************************* 付费内容组件
const PaidContent = {
    props: {
        post_parent: {
            type: Number,
            default: 0
        },
        price: {
            type: Number,
            default: 0
        },
        file_id: {
            type: Number,
            default: 0
        },
        file_url: {
            type: String,
            default: ''
        },
    },
    emits: ['update:price'],
    // 注册的子组件
    components: {
        PostPriceInput,
        FileUploader
    },
    methods: {
        set_sku() {
            wnd_ajax_modal('common/wnd_sku_form', { 'post_id': this.post_parent })
        }
    },
    template: `
<div class="field is-horizontal">
	<div class="field-label is-normal">
		<label class="label is-hidden-mobile"><i class="fas fa-file-invoice-dollar"></i></label>
	</div>
	<div class="field-body is-block">
		<div class="columns">
			<div class="column">
            <post-price-input :post_parent="post_parent" v-model="price" @update:modelValue="$emit('update:price', $event)"></post-price-input>
			</div>
			<div class="column upload-field">
				<file-uploader :post_parent="post_parent" meta_key="file" :file_id="file_id" :file_url="file_url" is_paid="1"></file-uploader>
			</div>
		</div>
	</div>
</div>
    `
};

// ********************************* Vue 实例
// https://chatgpt.com/c/67fe0928-bb08-8004-9dae-ab8c2132efc7
class FormComponent {

    // dom APP 挂载点的父节点（动态调整高度需要）
    parent_node = null;

    // string 组件模板：留空则使用挂载点内部 dom
    template = ``;

    constructor(container) {
        this.parent_node = document.querySelector(container).parentNode;
    }

    // 定义 vue 数据
    data() { }

    // 将类转换为 Vue 组件对象
    toVueComponent() {
        const self = this;
        return {
            template: this.template,
            data() {
                return self.data();
            },
            methods: self.methods(),
            watch: self.watch(),
            computed: self.computed(),
            components: self.components(),
            created() {
                self.created.call(this);
            },
            mounted() {
                self.mounted.call(this);
            },
            updated() {
                self.updated.call(this);
            },
            beforeUnmount() {
                self.beforeUnmount.call(this);
            },
        };
    }

    // 自动扫描本类中所有方法，排除 vue 固有方法后，作为 methods，其中方法中的 this 仍为 vue 实例 （by ChatGPT）
    methods() {
        const out = {};
        const visited = new Set();
        const reserved = new Set([
            'constructor', 'data', 'methods', 'watch', 'components', 'template',
            'created', 'mounted', 'updated', 'beforeMount', 'beforeUpdate',
            , 'beforeUnmount', 'unmounted', 'toVueComponent', 'unmount'
        ]);

        let proto = Object.getPrototypeOf(this);
        while (proto && proto !== Object.prototype) {
            const names = Object.getOwnPropertyNames(proto);
            for (const name of names) {
                if (visited.has(name)) continue; // 防止子类覆盖父类后又重复注册
                if (reserved.has(name)) continue;

                const descriptor = Object.getOwnPropertyDescriptor(proto, name);
                if (typeof descriptor.value === 'function') {
                    visited.add(name);
                    out[name] = function (...args) {
                        return descriptor.value.apply(this, args); // `this` 是 Vue 实例
                    };
                }
            }

            proto = Object.getPrototypeOf(proto); // 往上递归
        }

        return out;
    }

    // 生命周期钩子
    created() { }

    mounted() {
        this.get_data();
        document.addEventListener("keydown", this.onKeydown);
        window.addEventListener("hashchange", this.get_data);
    }

    beforeUnmount() {
        console.log("触发销毁：vue form");
        document.removeEventListener("keydown", this.onKeydown);
        window.removeEventListener("hashchange", this.get_data);
    }

    // 注册的子组件
    components() {
        return {
            ThumbnailCard,
            RichEditor,
            DropdownSearch,
            MultiLevelDropdown,
            TagsInput,
            FileUploader,
            PostPriceInput,
            PaidContent,
        };
    }

    // 监听器
    // watch() {
    //     return {
    //         post: {
    //             handler(newVal) {
    //                 console.log(newVal.ID);
    //             },
    //             deep: true // 启用深度监听
    //         }
    //     };
    // }
    watch() { }

    // computed() {
    //     return {
    //         fullName() {
    //             rreturn this.post.post_title;
    //         },

    //         nameLength() {
    //             return this.fullName.length;
    //         }
    //     };
    // }
    computed() { }

    updated() {
        // console.log("updated");
        funTransitionHeight(this.parent_node, trs_time);
    }

    handle_submit() {
        const form = this.$refs.formRef;
        const fields = form.querySelectorAll('[required]');
        let isValid = true;

        fields.forEach(field => {
            if (!field.value.trim()) {
                isValid = false;
            }
        });

        if (isValid) {
            this.submit_data();
        }
    }

    onKeydown(event) {
        const isCmdOrCtrl = event.metaKey || event.ctrlKey;

        if (isCmdOrCtrl && event.key === "Enter") {
            event.preventDefault();
            this.handle_submit();
        }
    }

    addPrefixToKeys(obj, prefix) {
        return Object.fromEntries(
            Object.entries(obj).map(([key, value]) => [`${prefix}${key}`, value])
        );
    }

    resizeTextarea(e) {
        e.target.style['overflow-y'] = 'hidden';
        // 先重置高度，以便正确计算 scrollHeight
        e.target.style.height = 'auto';
        // 再将高度设置为内容的高度
        e.target.style.height = e.target.scrollHeight + 'px';
    }

    // 原始方法（业务逻辑）这里的 this 为 Vue 实例。将 methods 中的方法抽离出来的原因是，方便子类针对性重写
    async get_data() { }

    async submit_data() { }
}

class PostFormComponent extends FormComponent {

    action = "post/wnd_insert_post";

    // 默认数据：包含 post 主题，meta合集。缩略图，文件 ID 和 URL
    data() {
        return {
            res: {},
            post: {},
            meta: {},
            thumbnail: "",
            file_id: 0,
            file_url: "",

            parent_node: this.parent_node,
            action: this.action,

            loading: true,
            submitting: false,
            submit_res: {},

            has_error: false,
            link: "",
            msg: "",

            wnd: wnd
        };
    }

    // 原始方法（业务逻辑）这里的 this 为 Vue 实例。将 methods 中的方法抽离出来的原因是，方便子类针对性重写
    async get_data() {
        const hash = window.location.hash.slice(1);
        const params = new URLSearchParams(hash);
        const props = {};
        for (const [key, value] of params.entries()) {
            if (key !== "module") {
                props[key] = value;
            }
        }

        this.loading = true;
        let res = await wnd_query("wnd_get_post_edit", props);
        this.res = res;
        this.loading = false;

        if (res.status <= 0) {
            this.has_error = true;
            this.msg = res.msg;
            return;
        }
        this.has_error = false;

        this.init_data(res.data);
    }

    init_data(data) {
        // Vue.reactive() 快速把已有对象变成响应式，避免一一绑定属性。 @link https://cn.vuejs.org/api/reactivity-core#reactive
        this.post = Vue.reactive(data.post);
        this.meta = Vue.reactive(data.meta);

        this.post.post_title = this.post.post_title === "Auto-draft" ? "" : this.post.post_title;
        this.thumbnail = data.thumbnail;
        this.file_id = data.file_id;
        this.file_url = data.file_url;
    }

    async submit_data() {
        let meta_data = this.get_meta_data();
        let term_data = this.get_term_data();
        let post_data = this.addPrefixToKeys(this.post, "_post_");
        let extra_data = this.get_extra_data();

        // 组合数据发起请求
        let data = { ...meta_data, ...term_data, ...post_data, ...extra_data };
        this.submitting = true;
        let res = await wnd_ajax_action(this.action, data);
        this.submitting = false;
        if (res.status <= 0) {
            this.has_error = true;
            this.msg = res.msg;
            return;
        }

        this.has_error = false;
        this.msg = res.msg;
        this.link = res.data.url;
        this.submit_res = res;
    }

    // 返回 meta 数据 object
    get_meta_data() { }

    // 返回 分类数据 object
    get_term_data() { }

    // 其他附加数据
    get_extra_data() { }

    processTagOptions(termOptions, terms) {
        const options = termOptions.map((item) => ({
            name: item.name,
            value: item.slug,
        }));
        const selected = terms ? terms.map((item) => item.slug) : [];
        return { options, selected };
    }

    processCatOptions(termOptions, terms) {
        const selected = [];

        function processItems(items) {
            return items.map(item => {
                const option = {
                    name: item.name,
                    value: item.term_id,
                };

                // 如果当前项被选中，加入 selected
                if (terms && terms.some(t => t.term_id === item.term_id)) {
                    selected.push(item.term_id);
                }

                // 如果有 children，递归处理
                if (item.children && item.children.length > 0) {
                    option.children = processItems(item.children);
                }

                return option;
            });
        }

        const options = processItems(termOptions);
        return {
            options,
            selected: selected.length > 0 ? selected : [],
        };
    }

    onKeydown(event) {
        super.onKeydown(event);
        if (event.key === "F8") {
            event.preventDefault();
            this.submit_res = {};
            this.link = "";
            this.msg = ""
            this.get_data();
        }
    }
}