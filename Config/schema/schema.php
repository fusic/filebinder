<?php
class AppSchema extends CakeSchema {
    var $name = 'App';

    function before($event = array()) {
        return true;
    }

    function after($event = array()) {
    }

    var $attachments = array(
                           'id' => array('type' => 'integer', 'null' => false, 'default' => NULL, 'key' => 'primary'),
                           'model' => array('type' => 'text', 'null' => false, 'default' => NULL),
                           'model_id' => array('type' => 'integer', 'null' => false, 'default' => NULL),
                           'field_name' => array('type' => 'text', 'null' => false, 'default' => NULL),
                           'file_name' => array('type' => 'text', 'null' => false, 'default' => NULL),
                           'file_content_type' => array('type' => 'text', 'null' => false, 'default' => NULL),
                           'file_size' => array('type' => 'integer', 'null' => false, 'default' => NULL),
                           'file_object' => array('type' => 'longtext', 'null' => true, 'default' => NULL), // error
                           'created' => array('type' => 'timestamp', 'null' => true, 'default' => NULL),
                           'modified' => array('type' => 'timestamp', 'null' => true, 'default' => NULL),
                           'indexes' => array('PRIMARY' => array('column' => 'id', 'unique' => 1)),
                           'tableParameters' => array('charset' => 'utf8', 'collate' => 'utf8_general_ci', 'engine' => 'InnoDB')
                           );
  }