<?php
/* Attachment Fixture generated on: 2011-07-06 20:07:53 : 1309951553 */
class AttachmentFixture extends CakeTestFixture {
    var $name = 'Attachment';

    var $fields = array(
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

    var $records = array(
        array(
            'id' => 1,
            'model' => 'LandingBlock',
            'model_id' => 1,
            'field_name' => 'firstview_photo',
            'file_name' => 'photo.png',
            'file_content_type' => 'image/png',
            'file_size' => '771311',
            'file_object' => '',
            'parent_model_id' => 1,
            'created' => '2011-04-14 19:29:32',
            'modified' => '2011-04-14 19:29:32'
        ),
        array(
            'id' => 10000041,
            'model' => 'LandingBlock',
            'model_id' => 1000004,
            'field_name' => 'voice_type_image',
            'file_name' => 'photo.png',
            'file_content_type' => 'image/png',
            'file_size' => '771311',
            'file_object' => '',
            'parent_model_id' => 1,
            'created' => '2011-04-14 19:29:32',
            'modified' => '2011-04-14 19:29:32'
        ),
        array(
            'id' => 10000042,
            'model' => 'LandingBlock',
            'model_id' => 1000004,
            'field_name' => 'voice_type_voice0_photo',
            'file_name' => 'photo.png',
            'file_content_type' => 'image/png',
            'file_size' => '771311',
            'file_object' => '',
            'parent_model_id' => 1,
            'created' => '2011-04-14 19:29:32',
            'modified' => '2011-04-14 19:29:32'
        ),
        array(
            'id' => 10000043,
            'model' => 'LandingBlock',
            'model_id' => 1000004,
            'field_name' => 'voice_type_voice1_photo',
            'file_name' => 'photo.png',
            'file_content_type' => 'image/png',
            'file_size' => '771311',
            'file_object' => '',
            'parent_model_id' => 1,
            'created' => '2011-04-14 19:29:32',
            'modified' => '2011-04-14 19:29:32'
        ),
        array(
            'id' => 10000044,
            'model' => 'LandingBlock',
            'model_id' => 1000004,
            'field_name' => 'voice_type_voice2_photo',
            'file_name' => 'photo.png',
            'file_content_type' => 'image/png',
            'file_size' => '771311',
            'file_object' => '',
            'parent_model_id' => 1,
            'created' => '2011-04-14 19:29:32',
            'modified' => '2011-04-14 19:29:32'
        ),

        // landing.test.php
        array(
            'id' => 20000001,
            'model' => 'Landing',
            'model_id' => 1,
            'field_name' => 'brand_logo',
            'file_name' => 'test.png',
            'file_content_type' => 'image/png',
            'file_size' => '771311',
            'file_object' => '',
            'parent_model_id' => 1,
            'created' => '2011-04-14 19:29:32',
            'modified' => '2011-04-14 19:29:32'
        ),
    );
}