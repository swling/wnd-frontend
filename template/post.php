<?php
// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

/** 
*@see
*自定义一些标准模块以便在页面或ajax请求中快速调用
*函数均以echo直接输出返回
*以_wnd_做前缀的函数可用于ajax请求，无需nonce校验，因此相关模板函数中不应有任何数据库操作，仅作为界面响应输出。
*/

/**
*@since 2019.01.20 
*快速编辑文章状态表单
*/
function _wnd_post_status_form($post_id){

	$post_status = get_post_status( $post_id );
	switch ($post_status) {

		case 'publish':
			$status_text = '已发布';
			break;

		case 'pending':
			$status_text = '待审核';
			break;	

		case 'draft':
			$status_text = '草稿';
			break;

		case false:
			$status_text = '已删除';
			break;					

		default:
			$status_text = $post_status;
			break;
	}

?>
<form id="post-status" action="" method="post" onsubmit="return false">
	<div class="field">
		<div class="ajax-msg"></div>
	</div>
	<div class="field text-centered">
		<label class="radio">
			<input type="radio" class="radio" name="post_status" value="publish" <?php if($post_status=='publish' ) echo ' checked="checked" ' ?>>
			发布
		</label>

		<label class="radio">
			<input type="radio" class="radio" name="post_status" value="draft" <?php if($post_status=='draft') echo ' checked="checked" ' ?>>
			草稿
		</label>

		<label class="radio">
			<input type="radio" class="radio" name="post_status" value="delete">
			<span class="is-danger">删除</span>
		</label>
	</div>
	<?php if(wnd_is_manager()) { ?>
	<div class="field">
		<textarea name="remark" class="textarea" placeholder="备注（可选）"></textarea>
	</div>
	<?php } ?>
	<input type="hidden" name="post_id" value="<?php echo $post_id; ?>">
	<?php wp_nonce_field('wnd_update_post_status', '_ajax_nonce'); ?>
	<input type="hidden" name="action" value="wnd_action">
	<input type="hidden" name="action_name" value="wnd_update_post_status">
	<div class="field is-grouped is-grouped-centered">
		<button type="button" name="submit" class="button" onclick="wnd_ajax_submit('#post-status')">确认</button>
	</div>
</form>
<script>
wnd_ajax_msg("<?php echo '当前： '.$status_text;?>", "is-danger", "#post-status")
</script>
<?php

}

/**
*###################################################### 附件上传表单
*@since 2019.01.16
*/
function _wnd_upload_field($args) {

	$defaults = array(
		'id'=>'upload-file',
		'meta_key' => '',
		'is_image'=> 1,
		'post_parent' => 0,
		'save_size'=>array('width'=>0,'height'=>0),
		'thumb_size'=>array('width'=>200,'height'=>200),
		'default_thumb' => WNDWP_URL . '/static/images/default.jpg',
	);
	$args = wp_parse_args($args,$defaults);

	// 根据user type 查找目标文件
	$attachment_id = wnd_get_post_meta( $args['post_parent'], $args['meta_key']);
	$attachment_id = $attachment_id ?: wnd_get_user_meta( get_current_user_id(), $args['meta_key']);
	$attachment_url = wp_get_attachment_url($attachment_id);

	// 如果文件不存在，例如已被后台删除，删除对应meta key
	if(!$attachment_url){
		if($args['post_parent']){
			wnd_delete_post_meta($args['post_parent'],$args['meta_key']);
		}else{
			wnd_delete_user_meta(get_current_user_id(),$args['meta_key']);
		}
	}

	//根据上传类型，设置默认样式 
	if($args['is_image']==1){
		$attachment_url = $attachment_url ?: $args['default_thumb'];
	}else{
		$attachment_url = $attachment_url ?: false;
	}

	?>
<div id="<?php echo $args['id'];?>" class="upload-field field">
	<div class="ajax-msg"></div>
	<?php if ($args['is_image'] == 1) { // 1、图片类型，缩略图 ?>
	<div class="field">
		<a onclick="wnd_click('input[data-id=\'<?php echo $args['id'];?>\']')"><img class="thumb" src="<?php echo $attachment_url; ?>" height="<?php echo $args['thumb_size']['height']?>" width="<?php echo $args['thumb_size']['width']?>" title="上传图像"></a>
		<a class="delete" data-id="<?php echo $args['id'];?>" data-attachment-id="<?php echo $attachment_id;?>"></a>
	</div>
	<div class="file">
		<input type="file" class="file-input" name="file[]" accept="image/*" data-id="<?php echo $args['id'];?>" />
	</div>
	<!-- 图片信息 -->
	<input type="hidden" name="file_save_width" value="<?php echo $args['save_size']['width']; ?>" />
	<input type="hidden" name="file_save_height" value="<?php echo $args['save_size']['height']; ?>" />
	<input type="hidden" name="file_default_thumb" value="<?php echo $args['default_thumb'] ?>" />
	<?php } else { ///2、文件上传 ?>
	<div class="columns is-mobile">
		<div class="column">
			<div class="file has-name is-fullwidth">
				<label class="file-label">
					<input type="file" class="file-input" name="file[]" data-id="<?php echo $args['id'];?>" />
					<span class="file-cta">
						<span class="file-icon">
							<i class="fa fa-upload"></i>
						</span>
						<span class="file-label">
							选择文件
						</span>
					</span>
					<span class="file-name">
						<?php if($attachment_url) echo '已上传：<a href="'.$attachment_url.'">查看文件</a>'; else echo'……';?>
					</span>
				</label>
			</div>
		</div>
		<div class="column is-narrow">
			<a class="delete" data-id="<?php echo $args['id'];?>" data-attachment-id="<?php echo $attachment_id;?>"></a>
		</div>
	</div>
	<?php } ?>
	<!-- 自定义属性，用于区分上传用途，方便后端区分处理 -->
	<input type="hidden" name="file_post_parent" value="<?php echo $args['post_parent'] ?>" />
	<input type="hidden" name="file_meta_key" value="<?php echo $args['meta_key']; ?>" />
	<input type="hidden" name="file_is_image" value="<?php if($args['is_image']==1) echo '1'; else echo '0'; ?>" />
	<?php wp_nonce_field('wnd_upload_file','file_upload_nonce');?>
	<?php wp_nonce_field('wnd_delete_attachment','file_delete_nonce');?>
</div>
<?php

}

/**
*@since 2019.01.30 付费内容表单字段
*/
function _wnd_paid_field($post_parent){

    $args = array(
        'id'=>'upload-file',
        'meta_key' => 'file',
        'is_image'=> 0,
        'post_parent' => $post_parent,
    );
    _wnd_upload_field($args);

?>
<div class="field">
	<div class="control has-icons-left">
		<input type="number" min="0" step="0.01" class="input" value="<?php echo get_post_meta( $post_parent, 'price', 1)?>" name="_wpmeta_price" placeholder="价格">
		<span class="icon is-left">
			<i class="fa fa-money"></i>
		</span>
	</div>
</div>
<?php
}

/**
*@since 2019.01.31 发布/编辑文章通用模板
*/
function _wnd_post_form($args=array()){

	$defaults = array(
		'post_id'	=> 0,
		'post_type' => 'post',
		'is_free'	=> 1,
		'has_excerpt' => 1,
	);
	$args = wp_parse_args($args,$defaults);
	$post_id = $args['post_id'];
	$post_type = $args['post_type'];

	// 未指定id，新建文章，否则为编辑
	if(!$post_id){
    	$action = wnd_get_draft_post($post_type = $post_type, $interval_time = 3600*24 );
    	$post_id = $action['msg'];
    }

    // 获取文章并定义数据
    $post = get_post($post_id);
    if($post){
    	$post_type = $post->post_type;
    	$args['is_free'] = get_post_meta( $post_id, 'price', 1 );

    //新建文章失败
	}else{

		// 已知用户创建失败，说明产生了错误，退出
		if(is_user_logged_in()){
			echo "<script>wnd_alert_msg('".$action['msg']."')</script>";
			return;  
		}
		// 匿名用户不允许创建草稿，上传，因而初始化一个空对象
		$post = new stdClass();
		$post->post_title ='';
		$post->post_excerpt ='';
		$post->post_content ='';		
	}

/**
*@since 2019.02.01 
*获取指定 post type的所有注册taxonomy
*当一个taxonomy 关联多个 post type 时，通过指定post type无法获取（应该为WordPress bug）。
*解决办法全部获取，然后遍历taxonomy数据中的 object_type 是否包含当前指定 post type（好在taxonomy通常只有几个或十来个）
*/
$cat_taxonomies = array();
$tag_taxonomies = array();
$taxonomies = get_taxonomies(array('public'=> true), 'object', 'and' );

if ( $taxonomies ) {
  foreach ( $taxonomies  as $taxonomy ) {
  	// 未关联当前分类
  	if(!in_array($post_type,$taxonomy->object_type)){
  		continue;
  	}
  	// 根据是否具有层级，推入分类数组或标签数组
    if( is_taxonomy_hierarchical($taxonomy->name) ){
        array_push($cat_taxonomies, $taxonomy->name);
    }else{
        array_push($tag_taxonomies, $taxonomy->name);
    }
  }unset($taxonomy);
}

?>
<form id="new-post-<?php echo $post_id;?>" name="new_post" method="post" action="" onsubmit="return false;" onkeydown="if(event.keyCode==13){return false;}">
	<div class="field content">
		<h3><span class="icon"><i class="fa fa-edit"></i></span> ID: <?php echo $post_id;?></h3>
	</div>	
	<div class="ajax-msg"></div>
	<div class="field">
		<label class="label">标题<span class="required">*</span></label>
		<div class="control">
			<input type="text" class="input" name="_post_post_title" required="required" value="<?php if($post->post_title!=='Auto-draft') echo $post->post_title;?>" placeholder="标题">
		</div>
	</div>

<?php
if($cat_taxonomies){
echo '<div class="field is-horizontal"><div class="field-body">'.PHP_EOL;
	 //遍历分类 
	foreach ($cat_taxonomies as $cat_taxonomy ) {
		$cat = get_taxonomy($cat_taxonomy);
    	// 获取当前文章已选择分类ID
    	$current_cat = get_the_terms($post_id, $cat_taxonomy);
    	$current_cat = $current_cat ? reset($current_cat) : 0;
    	$current_cat_id = $current_cat ? $current_cat->term_id : 0;
?>
	<div class="field">
		<label for="cat" class="label"><?php echo $cat->labels->name;?><span class="required">*</span></label>
		<div class="select">
			<?php wp_dropdown_categories('show_option_none=—选择'.$cat->labels->name.' * —&required=true&name=_term_'.$cat_taxonomy.'&taxonomy='.$cat_taxonomy.'&orderby=name&hide_empty=0&hierarchical=1&selected=' . $current_cat_id );?>
		</div>
	</div>
<?php

	}unset($cat_taxonomy);
echo '</div></div>'.PHP_EOL;
}
?>

<?php
	// 遍历标签
	foreach ($tag_taxonomies as $tag_taxonomy ) {
		// 排除WordPress原生 文章格式类型
		if($tag_taxonomy == 'post_format'){
			continue;
		}
		$tag = get_taxonomy($tag_taxonomy);
?>
	<div class="field">
		<label class="label"><?php echo $tag->labels->name;?></label>
		<div class="control">
			<input type="text" id="tags" class="input" name="_term_<?php echo $tag_taxonomy; ?>" value="<?php wnd_post_terms_text($post_id, $tag_taxonomy);?>" >
		</div>
	</div>
<?php 	
	}unset($tag_taxonomy); 
?>	

<?php if($args['has_excerpt']==1) { //摘要 ?>
	<div class="field">
		<label class="label">摘要</label>
		<div class="control">
			<textarea name="_post_post_excerpt" class="textarea" placeholder="摘要"><?php echo $post->post_excerpt;?></textarea>
		</div>
	</div>
<?php } ?>

<?php if($args['is_free']!=1 ) { //付费内容 
		echo '<label class="label">付费内容</label>';
		_wnd_paid_field($post_id);
	}
?>
<?php do_action( '_wnd_post_form', $post_id,$post_type,$post ); ?>

	<div class="field">
<?php 
	// 正文详情
	if(wp_doing_ajax()){

		echo '<textarea class="textarea" name="_post_post_content" placeholder="详情"></textarea>';

	}else {

        if(isset($post)){
        	$post = $post;
        	wp_editor( $post->post_content, '_post_post_content','media_buttons=1');
        }else{
        	wp_editor( $post->post_content, '_post_post_content','media_buttons=0');
        }
	}
?>
	</div>

	<input type="hidden" name="_post_post_type" value="<?php echo $post_type;?>">
	<?php if($post_id) { ?>
	<input type="hidden" name="_post_post_id" value="<?php echo $post_id; ?>">
	<?php }?>
	<input type="hidden" name="action" value="wnd_action">
	<input type="hidden" name="action_name" value="wnd_insert_post">
	<?php wp_nonce_field('wnd_insert_post', '_ajax_nonce'); ?>
	<?php if(is_user_logged_in()) { ?>
	<div class="field">
		<div class="control">
			<label class="checkbox">
				<input type="checkbox" name="_post_post_status" value="draft">
				存为草稿
			</label>
		</div>
	</div>
	<?php }?>
	<div class="field is-grouped is-grouped-centered">
		<button type="button" name="submit" class="button" onclick="wnd_ajax_submit('#new-post-<?php echo $post_id?>')">提交</button>
	</div>	
</form>
<?php

}

/**
*@since ≈2018.07
*###################################################### 表单设置：标签编辑器
*/
function wnd_tags_editor($maxTags=3, $maxLength=10, $placeholder='标签', $taxonomy='' ,$initialTags='' ){

?>
<!--jquery标签编辑器 Begin-->
<script src="<?php echo WNDWP_URL.'static/js/jquery.tag-editor.js' ?>"></script>
<script src="<?php echo WNDWP_URL.'static/js/jquery.caret.min.js' ?>"></script>
<link rel="stylesheet" href="<?php echo WNDWP_URL.'static/css/jquery.tag-editor.css' ?>">
<script>
jQuery(document).ready(function($) {
	$('#tags').tagEditor({
		//自动提示 
		autocomplete: {
			delay: 0,
			position: {
				collision: 'flip'
			},
			source: [<?php if($taxonomy) wnd_terms_text($taxonomy,100);?>]
			// source: ['ActionScript', 'AppleScript', 'Asp', 'BASIC']  //demo
		},
		forceLowercase: false,
		placeholder: '<?php echo $placeholder; ?>',
		maxTags: '<?php echo $maxTags; ?>', //最多标签个数
		maxLength: '<?php echo $maxLength; ?>', //单个标签最长字数
		onChange: function(field, editor, tags) {
			// alert("变了");
		},
		// 预设标签
		initialTags: [<?php if($initialTags) echo $initialTags; ?>],
		// initialTags: ['ActionScript', 'AppleScript', 'Asp', 'BASIC'], //demo
	});
});
</script>
<?php    
    
}

// ###################################################################################
// 以文本方式输出当前文章标签、分类名称 主要用于前端编辑器输出形式： tag1, tag2, tag3
function wnd_post_terms_text($post_id, $taxonomy) {
	$terms = wp_get_object_terms($post_id, $taxonomy );
	if (!empty($terms)) {
		if (!is_wp_error($terms)) {
		    $terms_list = '';
			foreach ($terms as $term) {
				$terms_list .= $term->name . ',';
			}

			// 移除末尾的逗号
			echo rtrim( $terms_list,",");
		}
	}
}

//###################################################################################
 // 以文本方式列出热门标签，分类名称 用于标签编辑器，自动提示文字： 'tag1', 'tag2', 'tag3'
function wnd_terms_text($taxonomy,$number){

	$terms = get_terms( $taxonomy, 'orderby=count&order=DESC&hide_empty=0&number='.$number );
	if (!empty($terms)) {
		if (!is_wp_error($terms)) {
		    $terms_list = '';
			foreach ($terms as $term) {
				$terms_list .= '\''.$term->name . '\',';
			}

			// 移除末尾的逗号
			echo rtrim( $terms_list,",");
		}
	}	

}