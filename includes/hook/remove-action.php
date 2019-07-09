<?php

/**
 *@since 2019.07.09 移除WordPress定时自动删除“自动草稿”
 *本插件设置了自动草稿重用机制，故此无需删除自动草稿
 **/
remove_action('wp_scheduled_auto_draft_delete', 'wp_delete_auto_drafts');
