<?php

class Slide extends DataObject
{
    static $db = array(
        'SortOrder' => 'Int',
        'Content'   => 'HTMLText',
        'Link'      => 'Text'
    );

    static $default_sort = 'SortOrder';

    static $has_one = array(
        'Page' => 'Page',
        'Image' => 'Image'
    );

    public function getCMSFields()
    {
        return new FieldList(array(
            new UploadField('Image', 'Image (1440x460)'),
            new TextField('Link', 'Link to'),
            new HtmlEditorField('Content', 'Content')
        ));
    }

    public function summaryFields()
    {
        return array(
            'Image.CMSThumbnail' => 'Image',
        );
    }
}