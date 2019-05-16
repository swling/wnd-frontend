<?php

function _wnd_breadcrumb() {

	$html = '';

	$queried_object = get_queried_object();

	if (is_single()) {

		$html .= $queried_object->post_title;

	} elseif (is_page()) {

		$html .= $queried_object->post_title;

	} elseif (is_tax()) {

		$html .= $queried_object->name;

	} elseif (is_category()) {

		$html .= $queried_object->name;

	} elseif (is_tag()) {

		$html .= $queried_object->name;

	}

	return $html ? '<nav class="breadcrumb" aria-label="breadcrumbs">' . $html . '</nav>' : null;

}