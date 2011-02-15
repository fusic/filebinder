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
        $defaults = array('model' => 'Attachment', // attachment model
                          'filePath' => WWW_ROOT . 'img' . DS, // default attached file path
                          'dbStorage' => true, // file entity save table
                          'beforeAttach' => null, // hook function
                          'afterAttach' => null // hook function
                          );

        $this->model = $model;

        // Merge settings
        $this->settings = Set::merge($defaults, $settings);
        $this->model->bindedModel = $this->settings['model'];
        App::import('Model', $this->settings['model']);
        $this->bindedModel =& ClassRegistry::init($this->settings['model']);

        // Set primaryKey
        $this->primaryKey = empty($model->primaryKey) ? 'id' : $model->primaryKey;
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
     * beforeFind
     *
     * @param &$model
     * @param $queryData
     * @return
     */
    function beforeFind(&$model, $queryData = null){
        if (empty($model->bindFields)) {
            return $queryData;
        }
        $this->bindFields = Set::combine($model->bindFields, '/field' , '/');
        if (empty($queryData['fields'])) {
            return $queryData;
        }

        $modelName = $model->alias;
        $fields = (array) $queryData['fields'];
        $flip = array_flip($fields);
        foreach ($this->bindFields as $fieldName => $data) {
            unset($flip[$modelName . '.' . $fieldName]);
            unset($flip[$fieldName]);
            if (!in_array($modelName . '.' . $fieldName, $fields) && !in_array($fieldName, $fields)) {
                unset($this->bindFields[$fieldName]);
            }
        }
        $queryData['fields'] = array_flip($flip);
        return $queryData;
    }

    /**
     * afterFind
     *
     * @param &$model, $result
     * @return
     */
    function afterFind(&$model, $result){

        $modelName = $model->alias;
        if (empty($model->bindFields) || empty($this->bindFields) || empty($result)) {
            return $result;
        }

        $bindFields = $this->bindFields;
        $model_ids = Set::extract('/' . $modelName . '/' . $this->primaryKey, $result);

        $query = array();
        $query['fields'] = array('id',
                                 'model',
                                 'model_id',
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
            if (empty($result[$key][$modelName])) {
                continue;
            }
            $model_id = $value[$modelName][$this->primaryKey];
            foreach ($bindFields as $fieldName => $bindValue) {
                if (array_key_exists($model_id . '.' . $fieldName, $binds)) {
                    $filePath = empty($bindFields[$fieldName]['filePath']) ? $this->settings['filePath'] : $bindFields[$fieldName]['filePath'];
                    $fileName = $binds[$model_id . '.' . $fieldName][$this->settings['model']]['file_name'];
                    $bind = $binds[$model_id . '.' . $fieldName][$this->settings['model']];
                    $bind['file_path'] = $filePath . $modelName . DS . $model_id . DS . $fieldName . DS . $fileName;
                    $bind['bindedModel'] = $this->bindedModel->alias;
                    $result[$key][$modelName][$fieldName] = $bind;

                    if ($this->settings['dbStorage'] && !file_exists($filePath . $modelName . DS . $model_id . DS . $fieldName . DS . $fileName)) {

                        /**
                         * create entity from record data
                         */
                        if ($this->withObject) {
                            $fileObject = $bind['file_object'];
                        } else {
                            $all = $this->bindedModel->findById($bind['id']);
                            $fileObject = $all[$this->settings['model']]['file_object'];
                        }

                        if (!$fileObject) {
                            continue;
                        }

                        mkdir($filePath . $modelName . DS . $model_id . DS . $fieldName . DS, 0755, true);
                        $bindFile = $filePath . $modelName . DS . $model_id . DS . $fieldName . DS . $fileName;
                        $fp = fopen($bindFile , 'w');
                        fwrite($fp, base64_decode($fileObject));
                        fclose($fp);

                        if (file_exists($bindFile)) {
                            /**
                             * afterAttach
                             */
                            if (!empty($this->settings['afterAttach'])) {
                                $res = false;
                                if (function_exists($this->settings['afterAttach'])) {
                                    $res = call_user_func($this->settings['afterAttach'], $bindFile);
                                } else {
                                    $res = call_user_func(array($model, $this->settings['afterAttach']), $bindFile);
                                }
                                if (!$res) {
                                    return false;
                                }
                            }
                        }
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
        $modelName = $model->alias;
        foreach ($model->data[$modelName] as $fieldName => $value) {
            if (!in_array($fieldName, Set::extract('/field', $model->bindFields))) {
                continue;
            }
            if (empty($value) || empty($value['tmp_bind_path'])) {
                continue;
            }

            $bind = $value;
            $bind['model_id'] = 0;

            $tmpFile = $value['tmp_bind_path'];
            if (file_exists($tmpFile)) {
                /**
                 * beforeAttach
                 */
                if (!empty($this->settings['beforeAttach'])) {
                    $res = false;
                    if (function_exists($this->settings['beforeAttach'])) {
                        $res = call_user_func($this->settings['beforeAttach'], $tmpFile);
                    } else {
                        $res = call_user_func(array($model, $this->settings['beforeAttach']), $tmpFile);
                    }
                    if (!$res) {
                        return false;
                    }
                }

                /**
                 * dbStorage
                 */
                if ($this->settings['dbStorage']) {
                    $fp = fopen($tmpFile, 'r');
                    $ofile = fread($fp, filesize($tmpFile));
                    fclose($fp);
                    $bind['file_object'] = base64_encode($ofile);
                }
            }

            $this->bindedModel->create();
            if (!$this->bindedModel->save($bind)) {
                return false;
            }

            $bind_id = $this->bindedModel->getLastInsertId();
            $model->data[$modelName][$fieldName]['bind_id']  = $bind_id;
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
        $modelName = $model->alias;

        if ($created) {
            $model_id = $model->getLastInsertId();
        } else {
            if (empty($model->data[$modelName][$this->primaryKey])) {
                // SoftDeletable
                return;
            }
            $model_id = $model->data[$modelName][$this->primaryKey];
        }

        $bindFields = Set::combine($model->bindFields, '/field' , '/');

        // set model_id
        foreach ($model->data[$modelName] as $fieldName => $value) {
            if (!in_array($fieldName, Set::extract('/field', $model->bindFields))) {
                continue;
            }

            $filePath = empty($bindFields[$fieldName]['filePath']) ? $this->settings['filePath'] : $bindFields[$fieldName]['filePath'];
            $bindDir = $filePath . $modelName . DS . $model_id . DS . $fieldName . DS;

            // Check delete_check
            if (!empty($model->data[$modelName]['delete_' . $fieldName])) {
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

            if (empty($value) || empty($value['tmp_bind_path'])) {
                continue;
            }

            $bind_id = $value['bind_id'];
            $tmpFile = $value['tmp_bind_path'];
            $bindFile = null;

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
                $bindFile = $bindDir . $value['file_name'];
                mkdir($bindDir, 0755, true);
                rename($tmpFile, $bindFile);
            }
            if ($bindFile && file_exists($bindFile)) {
                /**
                 * afterAttach
                 */
                if (!empty($this->settings['afterAttach'])) {
                    $res = false;
                    if (function_exists($this->settings['afterAttach'])) {
                        $res = call_user_func($this->settings['afterAttach'], $bindFile);
                    } else {
                        $res = call_user_func(array($model, $this->settings['afterAttach']), $bindFile);
                    }
                    if (!$res) {
                        return false;
                    }
                }
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
        $modelName = $model->alias;

        // Bind model
        $model->bindModel(array('hasMany' => array($this->settings['model'] => array(
                                                                                     'className' => $this->settings['model'],
                                                                                     'foreignKey' => 'model_id',
                                                                                     'dependent' => true,
                                                                                     'conditions' => array($this->settings['model'] . '.model' => $model->name)
                                                                                     ))), false);

        $query = array();
        $query['recursive'] = -1;
        $query['conditions'] = array($modelName . '.' . $this->primaryKey  => $model->id);
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
        $modelName = $model->alias;
        $model_id = $this->data[$modelName][$this->primaryKey];
        return $this->deleteEntity($model_id);
    }

    /**
     * deleteEntity
     *
     * @param mixed $model_id
     * @return
     */
    function deleteEntity($model_id = null){
        if (!$model_id) {
            return false;
        }
        $modelName = $this->model->alias;
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
     * alphaNumericFileName
     * Validation method: alpha number only
     *
     * @param $
     * @return
     */
    function alphaNumericFileName(&$model, $value){
        $file = array_shift($value);
        if (!is_array($file)) {
            return false;
        }
        if (in_array('allowEmpty', $file)) {
            return false;
        }

        $fileName = $file['file_name'];

        // alphaNumeric + .
        if (!preg_match('/^[\p{Ll}\p{Lm}\p{Lo}\p{Lt}\p{Lu}\p{Nd}.]+$/mu', $fileName)) {
            return false;
        }
        return true;
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
     * checkMaxFileSize
     * Validation method: check min file size
     *
     * @param &$model
     * @param $value
     * @param $max
     * @return
     */
    function checkMaxFileSize(&$model, $value, $max){
        $file = array_shift($value);
        if (!is_array($file)) {
            return false;
        }
        if (in_array('allowEmpty', $file)) {
            return false;
        }

        $fileSize = $file['file_size'];

        if ($fileSize >= $max) {
            return false;
        }
        return true;
    }

    /**
     * checkMinFileSize
     * Validation method: check min file size
     *
     * @param &$model
     * @param $value
     * @param $min
     * @return
     */
    function checkMinFileSize(&$model, $value, $min){
        $file = array_shift($value);
        if (!is_array($file)) {
            return false;
        }
        if (in_array('allowEmpty', $file)) {
            return false;
        }

        $fileSize = $file['file_size'];

        if ($fileSize <= $min) {
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
        return $this->checkMaxFileSize($model, $value, $max);
    }

    /**
     * notEmptyFile
     * Validation method: check is empty
     *
     * @param &$model
     * @param $value
     * @return
     */
    function notEmptyFile(&$model, $value){
        return $this->checkMinFileSize($model, $value, -1);
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
