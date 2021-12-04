# checkbox
```php
$form->add_checkbox(
    [
        'id'       => 'country-select',
        'name'     => '_term_country[]',
        'options'  => ['中国' => 'china', '美国' => 'usa', '德国' => 'de', '法国' => 'fr'],
        'required' => true,
        'label'    => 'Country',
        'max'      => 2, // 限制 checkbox 最多选择数量
    ]
);
```