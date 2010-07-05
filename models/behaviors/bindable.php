<?php
class BindableBehavior extends ModelBehavior {

    var $settings = array();
    var $withObject = false;

    /**
     * setup
     *
     * @param &$model
     * @param $settings
     */
    function setup(&$model, $settings = array()){
        $defaults = array('model' => 'Attachment', // attach model
                          'filePath' => WWW_ROOT . 'img' . DS, // default attached file path
                          'dbStorage' => true // file entity save table
                          );

        $this->model = $model;

        // Merge settings
        $this->settings = Set::merge($defaults, $settings);

        App::import('Model', $this->settings['model']);
        $this->bindedModel =& ClassRegistry::init($this->settings['model']);

        // Set primalyKey
        $this->primalyKey = empty($model->primalyKey) ? 'id' : $model->primalyKey;
    }

    /**
     * beforeValidate
     *
     * @param &$model
     * @return
     */
    function beforeValidate(&$model){
        return true;
    }

    /**
     * withObject
     * find attachment with file object
     *
     * @param $arg
     * @return
     */
    function withObject(){
        $this->withObject = true;
    }

    /**
     * afterFind
     *
     * @param &$model, $result
     * @return
     */
    function afterFind(&$model, $result){

        $modelName = $model->name;
        $bindFields = Set::combine($model->bindFields, '/field' , '/');
        $model_ids = Set::extract('/' . $modelName . '/' . $this->primalyKey, $result);

        $query = array();
        $query['fields'] = array('model_id',
                                 'field_name',
                                 'file_name',
                                 'file_content_type',
                                 'file_size',
                                 'created',
                                 'modified');
        // with Object
        if ($this->withObject) {
            $query['fields'][] = 'file_object';
        }

        $query['recursive'] = -1;
        $query['conditions'] = array('model' => $modelName,
                                     'model_id' => $model_ids);

        $binds = $this->bindedModel->find('all', $query);
        $binds = Set::combine($binds, array('%1$s.%2$s' , '/' . $this->settings['model'] . '/model_id', '/' . $this->settings['model'] . '/field_name'), '/' . $this->settings['model']);
        foreach ($result as $key => $value) {
            if (empty($value[$modelName])) {
                continue;
            }
            $model_id = $value[$modelName][$this->primalyKey];
            foreach ($bindFields as $fieldName => $bindValue) {
                if (array_key_exists($model_id . '.' . $fieldName, $binds)) {
                    $filePath = empty($bindFields[$fieldName]['filePath']) ? $this->settings['filePath'] : $bindFields[$fieldName]['filePath'];
                    $fileName = $binds[$model_id . '.' . $fieldName][$this->settings['model']]['file_name'];
                    $bind = $binds[$model_id . '.' . $fieldName][$this->settings['model']];
                    $bind['file_path'] = $filePath . $modelName . DS . $model_id . DS . $fieldName . DS . $fileName;
                    $result[$key][$modelName][$fieldName] = $bind;

                    if ($this->settings['dbStorage'] && !file_exists($filePath . $modelName . DS . $model_id . DS . $fieldName . DS . $fileName)) {
                        // create entity from record data
                        mkdir($filePath . $modelName . DS . $model_id . DS . $fieldName . DS, 0755, true);
                        $fp = fopen($filePath . $modelName . DS . $model_id . DS . $fieldName . DS . $fileName, 'w');
                        fwrite($fp, base64_decode($bind['file_object']));
                        fclose($fp);
                    }
                } else {
                    $result[$key][$modelName][$fieldName] = null;
                }
            }
        }

        return $result;
    }

    /**
     * beforeSave
     *
     * @param &$model
     * @return
     */
    function beforeSave(&$model) {
        foreach ($model->data[$model->name] as $fieldName => $value) {
            if (!in_array($fieldName, Set::extract('/field', $model->bindFields))) {
                continue;
            }
            if (empty($value)) {
                continue;
            }

            $bind = $value;
            $bind['model_id'] = 0;

            if ($this->settings['dbStorage']) {
                $tmpFile = $value['tmp_bind_path'];
                $fp = fopen($tmpFile, 'r');
                $ofile = fread($fp, filesize($tmpFile));
                fclose($fp);
                $bind['file_object'] = base64_encode($ofile);
            }

            $this->bindedModel->create();
            if (!$this->bindedModel->save($bind)) {
                return false;
            }

            $bind_id = $this->bindedModel->getLastInsertId();
            $model->data[$model->name][$fieldName]['bind_id']  = $bind_id;
        }

        return true;
    }

    /**
     * afterSave
     *
     * @param &$model
     * @param $created
     * @return
     */
    function afterSave(&$model, $created){
        if ($created) {
            $model_id = $model->getLastInsertId();
        } else {
            $model_id = $model->data[$model->name][$this->primalyKey];
        }

        $bindFields = Set::combine($model->bindFields, '/field' , '/');

        // set model_id
        foreach ($model->data[$model->name] as $fieldName => $value) {
            if (!in_array($fieldName, Set::extract('/field', $model->bindFields))) {
                continue;
            }

            $modelName = $model->name;
            $filePath = empty($bindFields[$fieldName]['filePath']) ? $this->settings['filePath'] : $bindFields[$fieldName]['filePath'];
            $bindDir = $filePath . $modelName . DS . $model_id . DS . $fieldName . DS;

            // Check delete_check
            if (!empty($model->data[$model->name]['delete_' . $fieldName])) {
                // Delete record
                $conditions = array('model' => $modelName,
                                    'model_id' => $model_id,
                                    'field_name' => $fieldName);
                $this->bindedModel->deleteAll($conditions);

                if (file_exists($bindDir)) {
                    $this->recursiveRemoveDir($bindDir);
                }
                continue;
            }

            if (empty($value)) {
                continue;
            }

            $bind_id = $value['bind_id'];
            $tmpFile = $value['tmp_bind_path'];

            $bind = array();
            $bind['id'] = $bind_id;
            $bind['model_id'] = $model_id;

            // Delete Current record
            if (!$created) {
                $conditions = array('model' => $modelName,
                                    'model_id' => $model_id,
                                    'field_name' => $fieldName);
                $this->bindedModel->deleteAll($conditions);
            }

            $this->bindedModel->create();
            if (!$this->bindedModel->save($bind)) {
                return false;
            }

            if (file_exists($tmpFile)) {
                if (file_exists($bindDir)) {
                    $this->recursiveRemoveDir($bindDir);
                }
                mkdir($bindDir, 0755, true);
                rename($tmpFile, $bindDir . $value['file_name']);
            }
        }

    }

    /**
     * beforeDelete
     *
     * @param &$model
     * @return
     */
    function beforeDelete(&$model){
        // Bind model
        $model->bindModel(array('hasMany' => array($this->settings['model'] => array(
                                                                                     'className' => $this->settings['model'],
                                                                                     'foreignKey' => 'model_id',
                                                                                     'dependent' => true,
                                                                                     'conditions' => array($this->settings['model'] . '.model' => $model->name)
                                                                                     ))), false);

        $query = array();
        $query['recursive'] = -1;
        $query['conditions'] = array($model->name . '.' . $this->primalyKey  => $model->id);
        $this->data = $model->find('first', $query);
        return true;
    }

    /**
     * afterDelete
     *
     * @param &$model
     * @return
     */
    function afterDelete(&$model){
        $modelName = $model->name;
        $model_id = $this->data[$modelName][$this->primalyKey];
        return $this->deleteEntity($modelName, $model_id);
    }

    /**
     * deleteEntity
     *
     * @param string $modelName
     * @param mixed $model_id
     * @return
     */
    function deleteEntity($modelName = null, $model_id = null){
        if (!$modelName || !$model_id) {
            return false;
        }
        $bindFields = Set::combine($this->model->bindFields, '/field' , '/');
        $result = true;
        foreach ($bindFields as $fieldName => $value) {
            $filePath = empty($value['filePath']) ? $this->settings['filePath'] : $value['filePath'];
            $bindDir = $filePath . $modelName . DS . $model_id . DS;
            if (!$this->recursiveRemoveDir($bindDir)) {
                $result = false;
            }
        }
        return $result;
    }

    /**
     * recursiveRemoveDir
     * recursively remove directory
     *
     * @param $dir
     * @return
     */
    function recursiveRemoveDir($dir) {
        if (is_dir($dir)) {
            $objects = scandir($dir);
            foreach ($objects as $object) {
                if ($object != "." && $object != "..") {
                    if (filetype($dir."/".$object) == "dir") $this->recursiveRemoveDir($dir."/".$object); else unlink($dir."/".$object);
                }
            }
            reset($objects);
            return rmdir($dir);
        }
        return false;
    }

    /**
     * checkExtension
     * Validation method: check extention
     *
     * @param $model
     * @param $value
     * @param $extension
     * @return
     */
    function checkExtension(&$model, $value, $extension){
        $file = array_shift($value);
        if (!is_array($file)) {
            return false;
        }
        if (in_array('allowEmpty', $extension)) {
            $extension = array('jpg', 'gif', 'png'); // set default
        }
        $tmpFilePath = $file['tmp_bind_path'];

        $regexp = '/\.(' . implode('|', (array) $extension) . ')$/';
        if (!preg_match($regexp, $tmpFilePath)) {
            return false;
        }
        return true;
    }

    /**
     * checkContentType
     * Validation method: check MIME type
     *
     * @param &$model
     * @param $value
     * @param $mimeType
     * @return
     */
    function checkContentType(&$model, $value, $mimeType){
        $file = array_shift($value);
        if (!is_array($file)) {
            return false;
        }
        if (in_array('allowEmpty', $mimeType)) {
            return true;
        }

        $contentType = $file['file_content_type'];

        $regexp = '#^(' . implode('|', (array) $mimeType) . ')$#';
        if (!preg_match($regexp, $contentType)) {
            return false;
        }
        return true;
    }

    /**
     * checkFileSize
     * Validation method: check file size
     *
     * @param &$model
     * @param $value
     * @param $max
     * @return
     */
    function checkFileSize(&$model, $value, $max){
        $file = array_shift($value);
        if (!is_array($file)) {
            return false;
        }
        if (in_array('allowEmpty', $max)) {
            return false;
        }

        $fileSize = $file['file_size'];

        if ($fileSize > $max) {
            return false;
        }
        return true;
    }

    /**
     * funcCheckFile
     * Validation method: check file with user function
     *
     * @param &$model
     * @param $value
     * @param $func
     * @return
     */
    function funcCheckFile(&$model, $value, $func){
        $file = array_shift($value);
        if (!is_array($file)) {
            return false;
        }
        if (in_array('allowEmpty', $func)) {
            return false;
        }

        $tmpFilePath = $file['tmp_bind_path'];

        if (!file_exists($tmpFilePath)) {
            return false;
        }

        if (function_exists($func)) {
            $result = call_user_func($func, $tmpFilePath);
        } else {
            $result = call_user_func(array($model, $func), $tmpFilePath);
        }

        return $result;
    }
  }
?>