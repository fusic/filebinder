<?php
class RingComponent extends Object {

    var $tmpBindPath;
    var $components = array('Session');

    /**
     * initialize
     *
     * @param &$controller
     * @param $settions
     * @return
     */
    function initialize(&$controller, $settings = array()) {
        $this->tmpBindPath = TMP . 'cache/'; // default tmp file path

        $this->controller = $controller;
    }

    /**
     * startup
     *
     * @param &$controller
     * @return
     */
    function startup(&$controller){
        $controller->helpers[]  =  'Filebinder.Label';

        $this->Session->write('Filebinder.hash', Security::hash(time()));
    }

    /**
     * bindUp
     * set attach file
     *
     * @return
     */
    function bindUp ($modelName = null){
        if (empty($modelName)) {
            $modelName = $this->controller->modelClass;
        }
        if (empty($this->controller->data[$modelName])) {
            return true;
        }

        $bindFields = Set::combine($this->controller->{$modelName}->bindFields, '/field' , '/');

        foreach ($this->controller->data[$modelName] as $fieldName => $value) {
            if (!in_array($fieldName, Set::extract('/field', $this->controller->{$modelName}->bindFields))) {
                continue;
            }
            if (!is_array($value) || !isset($value['error'])) {
                continue;
            }
            if ($value['error'] == 4) {
                $this->controller->data[$modelName][$fieldName] = null;
                continue;
            }


            $fileName = $value['name'];
            $tmpFile = $value['tmp_name'];
            $contentType = $value['type'];
            $fileSize = filesize($tmpFile);

            $tmpPath = empty($bindFields[$fieldName]['tmpPath']) ? $this->tmpBindPath : $bindFields[$fieldName]['tmpPath'];

            $tmpBindPath = $tmpPath . 'ring_' . Security::hash($modelName . $fieldName . $fileName . time()) . $fileName;

            // move_uploaded_file
            move_uploaded_file($tmpFile, $tmpBindPath);

            $ring = array('model' => $modelName,
                          'field_name' => $fieldName,
                          'file_name' => $fileName,
                          'file_content_type' => $contentType,
                          'file_size' => $fileSize,
                          'tmp_bind_path' => $tmpBindPath);

            $this->controller->data[$modelName][$fieldName] = $ring;
        }
    }


  }