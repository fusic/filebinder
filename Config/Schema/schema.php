<?php
class attachementsSchema extends CakeSchema {
    public $name = 'attachements';

    public function before($event = array()) {
        return true;
    }

    public function after($event = array()) {
    }

    public $attachments = array(
                           'id' => array('type' => 'integer', 'null' => false, 'default' => NULL, 'key' => 'primary'),
                           'model' => array('type' => 'text', 'null' => false, 'default' => NULL),
                           'model_id' => array('type' => 'integer', 'null' => false, 'default' => NULL),
                           'field_name' => array('type' => 'text', 'null' => false, 'default' => NULL),
                           'file_name' => array('type' => 'text', 'null' => false, 'default' => NULL),
                           'file_content_type' => array('type' => 'text', 'null' => false, 'default' => NULL),
                           'file_size' => array('type' => 'integer', 'null' => false, 'default' => NULL),
                           'file_object' => array('type' => 'longtext', 'null' => true, 'default' => NULL), // error 'longtext' ...
                           'created' => array('type' => 'timestamp', 'null' => true, 'default' => NULL),
                           'modified' => array('type' => 'timestamp', 'null' => true, 'default' => NULL),
                           'indexes' => array('PRIMARY' => array('column' => 'id', 'unique' => 1)),
                           'tableParameters' => array('charset' => 'utf8', 'collate' => 'utf8_general_ci', 'engine' => 'InnoDB')
                           );
  }