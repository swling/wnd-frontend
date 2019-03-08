<?php

/**
 *适配本插件的ajax表单类
 *@since 2019.03.08
 */
class Wnd_Ajax_Form extends Wnd_Form {

	protected function build_form_header() {
		$html = '<form ';
		if (!is_null($this->method)) {
			$html .= ' method="' . $this->method . '"';
		}
		if (!is_null($this->action)) {
			$html .= ' action="' . $this->action . '"';
		}
		if ($this->upload) {
			$html .= ' enctype="multipart/form-data"';
		}

		if ($this->form_attr) {
			$html .= ' ' . $this->form_attr;
		}
		$html .= '>';
		$this->html = $html;
	}

	protected function build_submit_button() {
		$this->html .= '<div class="field is-grouped is-grouped-centered">';
		$this->html .= '<button type="submit" value="upload" class="button ' . $this->submit_style . ' ' . $this->get_size() . '">' . $this->submit . '</button>';
		$this->html .= '</div>';
	}

}