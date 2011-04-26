<?php
class RingComponent extends Object {

    var $components = array('Session');
    var $_autoBindDown = array();

    /**
     * initialize
     *
     * @param &$controller
     * @param $settions
     * @return
     */
    function initialize(&$controller, $settings = array()) {
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
     * Before render
     *
     * @param &$controller
     */
    function beforeRender(&$controller){
        if ($this->_autoBindDown) {
            foreach ($this->_autoBindDown as $i => $bindDownModel) {
                $this->bindDown($bindDownModel);
                unset($this->_autoBindDown[$i]);
            }
        }
    }

    /**
     * bindUp
     * set attach file
     *
     * @return
     */
    function bindUp($modelName = null, $withBindDown = false){
        if (empty($modelName)) {
            $modelName = $this->controller->modelClass;
        }
        if (!$model =& $this->_getModel($modelName)) {
            return false;
        }
        if (empty($this->controller->data[$model->alias])) {
            $this->Session->delete('Filebinder.' . $model->alias);
            return false;
        }

        $value = reset($this->controller->data[$model->alias]);
        $key = key($this->controller->data[$model->alias]);

        if (is_int($key) && is_array($value)) { // hasMany model data
            foreach ($this->controller->data[$model->alias] as $i => $data) {
                $this->_bindUp($model, $this->controller->data[$model->alias][$i], $i);
            }

        } else { // single model data
            $this->_bindUp($model, $this->controller->data[$model->alias]);
        }

        if ($withBindDown && !in_array($model->alias, $this->_autoBindDown)) {
            $this->_autoBindDown[] = $model->alias;
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
        if (!$model =& $this->_getModel($modelName)) {
            return false;
        }
        $this->Session->delete('Filebinder.' . $model->alias);
        if (empty($this->controller->data[$model->alias])) {
            return false;
        }

        $value = reset($this->controller->data[$model->alias]);
        $key = key($this->controller->data[$model->alias]);

        if (is_int($key) && is_array($value)) { // hasMany model data
            foreach ($this->controller->data[$model->alias] as $i => $data) {
                $this->_bindDown($model, $this->controller->data[$model->alias][$i], $i);
            }

        } else { // single model data
            $this->_bindDown($model, $this->controller->data[$model->alias]);
        }
    }

    /**
     * bindUp support method
     *
     * @param Model &$model
     * @param array &$data
     * @param int $i
     * @access protected
     */
    function _bindUp(&$model, &$data, $i = null)
    {
        $bindFields = Set::combine($model->bindFields, '/field' , '/');

        foreach ($data as $fieldName => $value) {
            if (!in_array($fieldName, Set::extract('/field', $model->bindFields))) {
                continue;
            }
            if (!$this->_checkFileUploaded($value) || $value['error'] == UPLOAD_ERR_NO_FILE) {
                $data[$fieldName] = null;
                continue;
            }

            $fileName = $value['name'];
            $tmpFile = $value['tmp_name'];
            $contentType = $value['type'];
            $fileSize = filesize($tmpFile);

            $tmpPath = empty($bindFields[$fieldName]['tmpPath']) ? TMP . 'cache/' : $bindFields[$fieldName]['tmpPath'];
            $tmpBindPath = $tmpPath . 'ring_' . Security::hash($model->alias . $fieldName . $fileName . time()) . $fileName;

            // move_uploaded_file
            move_uploaded_file($tmpFile, $tmpBindPath);

            $ring = array('model' => $model->alias,
                          'field_name' => $fieldName,
                          'file_name' => $fileName,
                          'file_content_type' => $contentType,
                          'file_size' => $fileSize,
                          'tmp_bind_path' => $tmpBindPath);

            $data[$fieldName] = $ring;
        }

        $sessionKey = is_int($i) ? "Filebinder.{$model->alias}.{$i}" : "Filebinder.{$model->alias}";

        if ($this->Session->check($sessionKey)) {
            $sessionData = $this->Session->read($sessionKey);
            foreach ($sessionData as $fieldName => $value) {
                if (empty($data[$fieldName])) {
                    $data[$fieldName] = $value;
                }
            }
        }
    }

    /**
     * bindDown support method
     *
     * @param Model &$model
     * @param array $data
     * @param int $i
     * @access protected
     */
    function _bindDown(&$model, $data, $i = null)
    {
        $sessionKey = is_int($i) ? "Filebinder.{$model->alias}.{$i}." : "Filebinder.{$model->alias}.";

        foreach ($data as $fieldName => $value) {
            if (!in_array($fieldName, Set::extract('/field', $model->bindFields))) {
                continue;
            }
            if (!$this->_checkBindUpped($value)) {
                continue;
            }
            if (isset($model->validationErrors[$fieldName])) {
                continue;
            }

            $this->Session->write($sessionKey . $fieldName, $value);
        }
    }

    /**
     * Find the model automatically
     *
     * @param string $modelName
     * @access Model
     * @access protected
     */
    function &_getModel($modelName)
    {
        $model = null;

        if (!empty($this->controller->{$modelName})) {
            $model =& $this->controller->{$modelName};

        } else if (!empty($this->controller->{$this->controller->modelClass}->{$modelName})) {
            $model =& $this->controller->{$this->controller->modelClass}->{$modelName};

        } else {
            $model =& ClassRegistry::init($modelName);
        }

        return $model;
    }

    /**
     * Check whether array is valid uploaded-data
     *
     * @param $array The array of uploaded-data
     * @return bool
     * @access protected
     */
    function _checkFileUploaded($array) {
        if (!is_array($array)) {
            return false;
        }

        $keys = array('name', 'type', 'tmp_name', 'error', 'size');
        return $this->_checkKeyExists($array, $keys);
    }

    /**
     * Check whether array is valid bind-upped data
     *
     * @param $array The array of bind-upped data
     * @return bool
     */
    function _checkBindUpped($array) {
        if (!is_array($array)) {
            return false;
        }

        $keys = array('field_name', 'file_name', 'file_content_type', 'file_size', 'tmp_bind_path');
        return $this->_checkKeyExists($array, $keys);
    }

    /**
     * Check whether array has keys
     *
     * @param $array The array
     * @param $keys The array of keys
     * @return bool
     * @access protected
     */
    function _checkKeyExists($array, $keys)
    {
        $diff = array_intersect_key(Set::normalize($keys), $array);

        if (count($keys) !== count($diff)) {
            return false;
        }

        return true;
    }
  }