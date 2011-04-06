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

        if (!isset($controller->noUpdateHash) || !$controller->noUpdateHash) {
            $this->Session->write('Filebinder.hash', Security::hash(time()));
        }
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
            $this->Session->delete('Filebinder.' . $modelName);
            return;
        }

        reset($this->controller->data[$modelName]);
        $key = key($this->controller->data[$modelName]);
        $value = current($this->controller->data[$modelName]);

        if (is_int($key) && is_array($value)) { // hasMany model data
            foreach ($this->controller->data[$modelName] as $i => $data) {
                $this->_bindUp($modelName, $this->controller->data[$modelName][$i], $i);
            }

        } else { // single model data
            $this->_bindUp($modelName, $this->controller->data[$modelName]);
        }
    }

    /**
     * bindDown
     * Check $this->data and recover uploaded file with session
     *
     * @return
     */
    function bindDown($modelName = null){
        if (empty($modelName)) {
            $modelName = $this->controller->modelClass;
        }
        $this->Session->delete('Filebinder.' . $modelName);
        if (empty($this->controller->data[$modelName])) {
            return;
        }

        reset($this->controller->data[$modelName]);
        $key = key($this->controller->data[$modelName]);
        $value = current($this->controller->data[$modelName]);

        if (is_int($key) && is_array($value)) { // hasMany model data
            foreach ($this->controller->data[$modelName] as $i => $data) {
                $this->_bindDown($modelName, $this->controller->data[$modelName][$i], $i);
            }

        } else { // single model data
            $this->_bindDown($modelName, $this->controller->data[$modelName]);
        }
    }

    function _bindUp($modelName, &$data, $i = null)
    {
        $model = $this->_getModel($modelName);
        $bindFields = Set::combine($model->bindFields, '/field' , '/');

        foreach ($data as $fieldName => $value) {
            if (!in_array($fieldName, Set::extract('/field', $model->bindFields))) {
                continue;
            }
            if (!is_array($value) || !isset($value['error'])) {
                continue;
            }
            if ($value['error'] == 4) {
                $data[$fieldName] = null;
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

            $data[$fieldName] = $ring;
        }

        $sessionKey = is_int($i) ? "Filebinder.{$modelName}.{$i}" : "Filebinder.{$modelName}";

        if ($this->Session->check($sessionKey)) {
            $sessionData = $this->Session->read($sessionKey);
            foreach ($sessionData as $fieldName => $value) {
                if (empty($data[$fieldName])) {
                    $data[$fieldName] = $value;
                }
            }
        }
    }

    function _bindDown($modelName, $data, $i = null)
    {
        $model = $this->_getModel($modelName);
        $sessionKey = is_int($i) ? "Filebinder.{$modelName}.{$i}." : "Filebinder.{$modelName}.";

        foreach ($data as $fieldName => $value) {
            if (!in_array($fieldName, Set::extract('/field', $model->bindFields))) {
                continue;
            }
            if (!is_array($value)) {
                continue;
            }
            if (isset($model->validationErrors[$fieldName])) {
                continue;
            }

            $this->Session->write($sessionKey . $fieldName, $value);
        }
    }

    function _getModel($modelName)
    {
        $model = null;

        if (!empty($this->controller->{$modelName})) {
            $model = $this->controller->{$modelName};

        } else if (!empty($this->controller->{$this->controller->modelClass}->{$modelName})) {
            $model = $this->controller->{$this->controller->modelClass}->{$modelName};

        } else {
            $model = ClassRegistry::init($modelName);
        }

        return $model;
    }
  }