<?php
class AttachmentFixture extends CakeTestFixture {
    public $name = 'Attachment';

    public $fields = array(
        'id' => array('type' => 'integer', 'null' => false, 'default' => NULL, 'length' => 20, 'key' => 'primary'),
        'model' => array('type' => 'text', 'null' => true, 'default' => NULL, 'collate' => 'utf8_general_ci', 'charset' => 'utf8'),
        'model_id' => array('type' => 'integer', 'null' => true, 'default' => NULL),
        'parent_model_id' => array('type' => 'integer', 'null' => true, 'default' => NULL),
        'field_name' => array('type' => 'text', 'null' => true, 'default' => NULL, 'collate' => 'utf8_general_ci', 'charset' => 'utf8'),
        'file_name' => array('type' => 'text', 'null' => true, 'default' => NULL, 'collate' => 'utf8_general_ci', 'charset' => 'utf8'),
        'file_content_type' => array('type' => 'text', 'null' => true, 'default' => NULL, 'collate' => 'utf8_general_ci', 'charset' => 'utf8'),
        'file_size' => array('type' => 'text', 'null' => true, 'default' => NULL, 'collate' => 'utf8_general_ci', 'charset' => 'utf8'),
        'file_object' => array('type' => 'text', 'null' => true, 'default' => NULL, 'collate' => 'utf8_general_ci', 'charset' => 'utf8'),
        'created' => array('type' => 'datetime', 'null' => true, 'default' => NULL),
        'modified' => array('type' => 'datetime', 'null' => true, 'default' => NULL),
        'indexes' => array('PRIMARY' => array('column' => 'id', 'unique' => 1)),
        'tableParameters' => array('charset' => 'utf8', 'collate' => 'utf8_general_ci', 'engine' => 'InnoDB')
    );

    public $records = array(
        array(
            'id' => 100,
            'model' => 'FilebinderPost',
            'model_id' => 1,
            'field_name' => 'logo',
            'file_name' => 'logo.png',
            'file_content_type' => 'image/png',
            'file_size' => '771311',
            'file_object' => '',
            'created' => '2011-08-22 19:29:32',
            'modified' => '2011-08-22 19:29:32'
        ),
    );
}