<?php
class BindableBehavior extends ModelBehavior {

    public $settings = array();
    public $runtime = array();

    const STORAGE_DB = 'Db';
    const STORAGE_S3 = 'S3';

    /**
     * setUp
     *
     * @param $model
     * @param $settings
     */
    public function setUp(Model $model, $settings = array()){
        $defaults = array('model' => 'Attachment', // attachment model
                          'filePath' => WWW_ROOT . 'img' . DS, // default attached file path
                          // 'dbStorage' => true, // backward compatible
                          'storage' => BindableBehavior::STORAGE_DB, // file entity save table
                          'beforeAttach' => null, // hook function
                          'afterAttach' => null, // hook function
                          'withObject' => false, // find attachment with file object
                          'exchangeFile' => true, // save new file after deleting old file
                          'dirMode' => 0755,
                          'fileMode' => 0644,
                          );

        $defaultRuntime = array('bindedModel' => null,
                                'primaryKey' => 'id',
                                );

        // Default settings
        $this->settings[$model->alias] = Set::merge($defaults, $settings);
        $this->runtime[$model->alias] = $defaultRuntime;

        // Set runtimes
        App::uses($this->settings[$model->alias]['model'], 'Model');
        $this->runtime[$model->alias]['bindedModel'] = ClassRegistry::init($this->settings[$model->alias]['model']);
        $this->runtime[$model->alias]['primaryKey'] = empty($model->primaryKey) ? 'id' : $model->primaryKey;
    }

    /**
     * setSettings
     *
     * @param $model, $settings
     */
    public function setSettings(Model $model, $settings){
        $before = $this->getSettings($model);
        $this->setUp($model, Set::merge($before, $settings));
    }

    /**
     * getSettings
     *
     * @param $model
     */
    public function getSettings(Model $model){
        return $this->settings[$model->alias];
    }

    /**
     * beforeValidate
     *
     * @param $model
     * @return
     */
    public function beforeValidate(Model $model){
        return true;
    }

    /**
     * withObject
     * find attachment with file object
     *
     * @param $model
     * @param $bool
     * @return
     */
    public function withObject(Model $model, $bool = true){
        $this->settings[$model->alias]['withObject'] = (bool)$bool;
    }

    /**
     * beforeFind
     *
     * @param $model
     * @param $queryData
     * @return
     */
    public function beforeFind(Model $model, $queryData = null){
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
     * @param $model, $result
     * @return
     */
    public function afterFind(Model $model, $result){
        return $this->bindFile($model, $result);
    }

    /**
     * beforeSave
     *
     * @param $model
     * @return
     */
    public function beforeSave(Model $model) {
        $modelName = $model->alias;
        $model->bindedData = $model->data;
        foreach ($model->data[$modelName] as $fieldName => $value) {
            if (!in_array($fieldName, Set::extract('/field', $model->bindFields))) {
                continue;
            }

            if (empty($value) || empty($value['tmp_bind_path'])) {
                unset($model->data[$modelName][$fieldName]);
                continue;
            }

            if (empty($value['file_size'])) {
                $model->data[$modelName][$fieldName] = null;
                $model->invalidate($fieldName, __('Validation Error: File Upload Error', true));
                return false;
            }

            $bind = $value;
            $bind['model'] = $modelName;
            $bind['model_id'] = 0;

            $tmpFile = $value['tmp_bind_path'];
            if (file_exists($tmpFile) && is_file($tmpFile)) {
                /**
                 * beforeAttach
                 */
                if (!empty($this->settings[$model->alias]['beforeAttach'])) {
                    $res = $this->_userfunc($model, $this->settings[$model->alias]['beforeAttach'], array($tmpFile));
                    if (!$res) {
                        return false;
                    }
                }

                /**
                 * Database storage
                 */
                $dbStorage = in_array(BindableBehavior::STORAGE_DB, (array)$this->settings[$model->alias]['storage']);
                // backward compatible
                if (isset($this->settings[$model->alias]['dbStorage'])) {
                    $dbStorage = $this->settings[$model->alias]['dbStorage'];
                }
                if ($dbStorage) {
                    $bind['file_object'] = base64_encode(file_get_contents($tmpFile));
                }
            }

            $this->runtime[$model->alias]['bindedModel']->create();
            if (!$data = $this->runtime[$model->alias]['bindedModel']->save($bind)) {
                return false;
            }

            $bind_id = $this->runtime[$model->alias]['bindedModel']->getLastInsertId();
            unset($model->data[$modelName][$fieldName]);
            $model->bindedData[$modelName][$fieldName] = $data[$this->runtime[$model->alias]['bindedModel']->alias] + array($this->runtime[$model->alias]['bindedModel']->primaryKey => $bind_id);
        }

        return true;
    }

    /**
     * afterSave
     *
     * @param $model
     * @param $created
     * @return
     */
    public function afterSave(Model $model, $created){
        $modelName = $model->alias;

        if ($created) {
            $model_id = $model->getLastInsertId();
        } else {
            if (empty($model->bindedData[$modelName][$this->runtime[$model->alias]['primaryKey']])) {
                // SoftDeletable
                return;
            }
            $model_id = $model->bindedData[$modelName][$this->runtime[$model->alias]['primaryKey']];
        }

        $bindFields = Set::combine($model->bindFields, '/field' , '/');
        $fields = Set::extract('/field', $model->bindFields);
        $deleteFields = array();

        foreach ($fields as $field) {
            $deleteFields[] = 'delete_' . $field;
        }

        // set model_id
        foreach ($model->bindedData[$modelName] as $fieldName => $value) {
            if (in_array($fieldName, $deleteFields) && $value) {
                $delete = true;
                $fieldName = substr($fieldName, 7);

            } else if (in_array($fieldName, $fields)) {
                $delete = false;

            } else {
                continue;
            }

            if ($delete || (!$created && !empty($value['tmp_bind_path']))) {
                unset($model->data[$modelName]['delete_' . $fieldName]);

                if ($delete || $this->settings[$model->alias]['exchangeFile']) {
                    $this->deleteEntity($model, $model_id, $fieldName);

                } else {
                    $this->runtime[$model->alias]['bindedModel']->deleteAll(array(
                                                                                  'model' => $modelName,
                                                                                  'model_id' => $model_id,
                                                                                  'field_name' => $fieldName
                                                                                  ));
                }
            }

            if (!is_array($value) || empty($value['tmp_bind_path'])) {
                continue;
            }

            $bind = array();
            $bind[$this->runtime[$model->alias]['bindedModel']->primaryKey] = $value[$this->runtime[$model->alias]['bindedModel']->primaryKey];
            $bind['model_id'] = $model_id;

            $this->runtime[$model->alias]['bindedModel']->create();
            if (!$this->runtime[$model->alias]['bindedModel']->save($bind)) {
                return false;
            }

            $baseDir = empty($bindFields[$fieldName]['filePath']) ? $this->settings[$model->alias]['filePath'] : $bindFields[$fieldName]['filePath'];
            if ($baseDir) {
                $filePath = $baseDir . $model->transferTo(array_diff_key(array('model_id' => $model_id) + $value, Set::normalize(array('tmp_bind_path'))));
            } else {
                $filePath = false;
            }
            $tmpFile = $value['tmp_bind_path'];

            if (file_exists($tmpFile) && is_file($tmpFile)) {

                /**
                 * Local file
                 */
                if ($filePath) {
                    if (!is_dir(dirname($filePath))) {
                        mkdir(dirname($filePath), $this->settings[$model->alias]['dirMode'], true);
                    }
                    if (!copy($tmpFile, $filePath) || !chmod($filePath, $this->settings[$model->alias]['fileMode'])) {
                        @unlink($tmpFile);
                        return false;
                    }
                }

                /**
                 * S3 storage
                 */
                $s3Storage = in_array(BindableBehavior::STORAGE_S3, (array)$this->settings[$model->alias]['storage']);
                if ($s3Storage) {
                    if (!class_exists('AmazonS3') || !Configure::read('Filebinder.S3.key') || !Configure::read('Filebinder.S3.secret')) {
                        //__('Validation Error: S3 Parameter Error');
                        @unlink($tmpFile);
                        return false;
                    }
                    $options = array('key' => Configure::read('Filebinder.S3.key'),
                                     'secret' => Configure::read('Filebinder.S3.secret'),
                                     );
                    $bucket = !empty($bindFields[$fieldName]['bucket']) ? $bindFields[$fieldName]['bucket'] : Configure::read('Filebinder.S3.bucket');
                    if (empty($bucket)) {
                        //__('Validation Error: S3 Parameter Error');
                        @unlink($tmpFile);
                        return false;
                    }
                    $s3 = new AmazonS3($options);
                    $region = !empty($bindFields[$fieldName]['region']) ? $bindFields[$fieldName]['region'] : Configure::read('Filebinder.S3.region');
                    if (!empty($region)) {
                        $s3->set_region($region);
                    }
                    $acl = !empty($bindFields[$fieldName]['acl']) ? $bindFields[$fieldName]['acl'] : Configure::read('Filebinder.S3.acl');
                    if (empty($acl)) {
                        $acl = AmazonS3::ACL_PUBLIC;
                    }
                    $responce = $s3->create_object($bucket,
                                                   $model->transferTo(array_diff_key(array('model_id' => $model_id) + $value, Set::normalize(array('tmp_bind_path')))),
                                                   array(
                                                         'fileUpload' => $filePath,
                                                         'acl' => $acl,
                                                         ));
                    if (!$responce->isOK()) {
                        //__('Validation Error: S3 Upload Error');
                        @unlink($tmpFile);
                        return false;
                    }
                }
            }

            if ($filePath && file_exists($filePath) && is_file($filePath)) {

                /**
                 * afterAttach
                 */
                if (!empty($this->settings[$model->alias]['afterAttach'])) {
                    $res = $this->_userfunc($model, $this->settings[$model->alias]['afterAttach'], array($filePath));
                    if (!$res) {
                        @unlink($tmpFile);
                        return false;
                    }
                }
            }
            @unlink($tmpFile);
        }

    }

    /**
     * afterDelete
     *
     * @param $model
     * @return
     */
    public function afterDelete(Model $model){
        $this->deleteEntity($model, $model->id);
        return true;
    }

    /**
     * Generate save path for binded file
     *
     * Notice: Don't use a random string or same functions.
     * This method used by afterSave and afterFind method,
     * When a random string is used, it doesn't generate correct file path.
     *
     * Bad function example:
     *   - mt_rand()
     *   - time()
     *   - uniqid()
     *   - etc..
     *
     * @param Model Model $model
     * @param array $data binded file data
     *   - id: binded model id
     *   - model: model name
     *   - model_id: model id
     *   - field_name: binding field name
     *   - file_name: uploaded file name
     *   - file_content_type: uploaded file content type
     *   - file_size: uploaded file size
     *   - created: created date
     *   - modified: modified date
     * @return string file path
     * @see BindableBehavior::afterSave()
     * @see BindableBehavior::afterFind()
     */
    public function transferTo(Model $model, $data) {
        return $model->alias . DS . $data['model_id'] . DS . $data['field_name'] . DS . $data['file_name'];
    }

    /**
     * deleteEntity
     *
     * @param Model $&model
     * @param mixed $modelId The model id
     * @return
     */
    public function deleteEntity(Model $model, $modelId, $fields = array()){
        if (!$deleteFields = $this->_findBindedFields($model, $modelId, $fields)) {
            return false;
        }

        $result = $this->runtime[$model->alias]['bindedModel']->deleteAll(array(
                                                                                'model' => $model->alias,
                                                                                'model_id' => $modelId,
                                                                                'field_name' => array_keys($deleteFields)
                                                                                ));

        if ($result) {
            $bindFields = Set::combine($model->bindFields, '/field' , '/');
            foreach ($bindFields as $fieldName => $value) {
                if (!isset($deleteFields[$fieldName])) {
                    continue;
                }

                /**
                 * S3 storage
                 */
                $s3Storage = in_array(BindableBehavior::STORAGE_S3, (array)$this->settings[$model->alias]['storage']);
                if ($s3Storage) {
                    if (!class_exists('AmazonS3') || !Configure::read('Filebinder.S3.key') || !Configure::read('Filebinder.S3.secret')) {
                        //__('Validation Error: S3 Parameter Error');
                        return false;
                    }
                    $options = array('key' => Configure::read('Filebinder.S3.key'),
                                     'secret' => Configure::read('Filebinder.S3.secret'),
                                     );
                    $bucket = !empty($bindFields[$fieldName]['bucket']) ? $bindFields[$fieldName]['bucket'] : Configure::read('Filebinder.S3.bucket');
                    if (empty($bucket)) {
                        //__('Validation Error: S3 Parameter Error');
                        return false;
                    }
                    $s3 = new AmazonS3($options);
                    $region = !empty($bindFields[$fieldName]['region']) ? $bindFields[$fieldName]['region'] : Configure::read('Filebinder.S3.region');
                    if (!empty($region)) {
                        $s3->set_region($region);
                    }
                    $responce = $s3->delete_object($bucket,
                                                   $model->transferTo($deleteFields[$fieldName])
                                                   );
                    if (!$responce->isOK()) {
                        //__('Validation Error: S3 Delete Error');
                        return false;
                    }
                }

                /**
                 * Local file
                 */
                $baseDir = empty($value['filePath']) ? $this->settings[$model->alias]['filePath'] : $value['filePath'];
                if ($baseDir) {
                $filePath = $baseDir . $model->transferTo($deleteFields[$fieldName]);
                } else {
                    $filePath = false;
                }

                if ($filePath && !@unlink($filePath)) {
                    $result = false;
                }
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
     * @access protected
     */
    protected function _recursiveRemoveDir($dir) {
        if (is_dir($dir)) {
            $objects = scandir($dir);
            foreach ($objects as $object) {
                if ($object != "." && $object != "..") {
                    if (filetype($dir."/".$object) == "dir") $this->_recursiveRemoveDir($dir."/".$object); else @unlink($dir."/".$object);
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
    public function alphaNumericFileName(Model $model, $value){
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
    public function checkExtension(Model $model, $value, $extension){
        $file = array_shift($value);
        if (!is_array($file)) {
            return false;
        }
        if (in_array('allowEmpty', $extension)) {
            $extension = array('jpg', 'jpeg', 'gif', 'png'); // set default
        }
        if (empty($file['tmp_bind_path']) && empty($file['file_path'])) {
            return false;
        }

        $tmpFilePath = empty($file['tmp_bind_path']) ? $file['file_path'] : $file['tmp_bind_path'];

        $regexp = '/\.(' . implode('|', (array) $extension) . ')$/i';
        if (!preg_match($regexp, $tmpFilePath)) {
            return false;
        }
        return true;
    }

    /**
     * checkContentType
     * Validation method: check MIME type
     *
     * @param $model
     * @param $value
     * @param $mimeType
     * @return
     */
    public function checkContentType(Model $model, $value, $mimeType){
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
     * @param $model
     * @param $value
     * @param $max
     * @return
     */
    public function checkMaxFileSize(Model $model, $value, $max){
        $file = array_shift($value);
        if (!is_array($file)) {
            return false;
        }
        if (in_array('allowEmpty', $file)) {
            return false;
        }

        $fileSize = $file['file_size'];
        $max = $this->calcFileSizeUnit($max);

        if ($fileSize >= $max) {
            return false;
        }
        return true;
    }

    /**
     * checkMinFileSize
     * Validation method: check min file size
     *
     * @param $model
     * @param $value
     * @param $min
     * @return
     */
    public function checkMinFileSize(Model $model, $value, $min){
        $file = array_shift($value);
        if (!is_array($file)) {
            return false;
        }
        if (in_array('allowEmpty', $file)) {
            return false;
        }

        $fileSize = $file['file_size'];
        $min = $this->calcFileSizeUnit($min);

        if ($fileSize <= $min) {
            return false;
        }
        return true;
    }

    /**
     * checkFileSize
     * Validation method: check file size
     *
     * @param $model
     * @param $value
     * @param $max
     * @return
     */
    public function checkFileSize(Model $model, $value, $max){
        return $this->checkMaxFileSize($model, $value, $max);
    }

    /**
     * notEmptyFile
     * Validation method: check is empty
     *
     * @param $model
     * @param $value
     * @return
     */
    public function notEmptyFile(Model $model, $value){
        return $this->checkMinFileSize($model, $value, -1);
    }

    /**
     * funcCheckFile
     * Validation method: check file with user function
     *
     * @param $model
     * @param $value
     * @param $func
     * @return
     */
    public function funcCheckFile(Model $model, $value, $func){
        $file = array_shift($value);
        if (!is_array($file)) {
            return false;
        }
        if (is_array($func)) {
            return false;
        }
        if (in_array('allowEmpty', $file)) {
            return false;
        }

        if (empty($file['tmp_bind_path']) && empty($file['file_path'])) {
            return false;
        }

        $tmpFilePath = empty($file['tmp_bind_path']) ? $file['file_path'] : $file['tmp_bind_path'];

        if (!file_exists($tmpFilePath) && is_file($tmpFilePath)) {
            return false;
        }

        $result = $this->_userfunc($model, $func, array($tmpFilePath));

        return $result;
    }

    /**
     * Calculate file size by unit
     *
     * e.g.) 100KB -> 1024000
     *
     * @param $size mixed
     * @return int file size
     */
    public function calcFileSizeUnit($size) {
        $units = array('K', 'M', 'G', 'T');
        $byte = 1024;

        if (is_numeric($size) || is_int($size)) {
            return $size;

        } else if (is_string($size) && preg_match('/^([0-9]+(?:\.[0-9]+)?)(' . implode('|', $units) . ')B?$/i', $size, $matches)) {
            return $matches[1] * pow($byte, array_search($matches[2], $units) + 1);
        }

        return false;
    }

    /**
     * Find attached file data from binded model
     *
     * @param $model $model
     * @param mixed $modelId The model id
     * @param mixed $fields The fields that searches in string or array
     * @return array
     * @access protected
     */
    protected function _findBindedFields(Model $model, $modelId, $fields = array()) {
        $query = array(
                       'conditions' => array(
                                             'model' => $model->alias,
                                             'model_id' => $modelId,
                                             ),
                       'fields' => array(
                                         'id', 'model', 'model_id', 'field_name', 'file_name',
                                         'file_content_type', 'file_size', 'created', 'modified'
                                         ),
                       'recursive' => -1
                       );

        if ($fields) {
            if (is_string($fields)) {
                App::uses('String', 'Utility');
                $fields = String::tokenize($fields);
            }

            $query['conditions']['field_name'] = $fields;
        }

        $data = $this->runtime[$model->alias]['bindedModel']->find('all', $query);

        if ($data) {
            $data = Set::combine(
                                 $data,
                                 '{n}.' . $this->runtime[$model->alias]['bindedModel']->alias . '.field_name',
                                 '{n}.' .  $this->runtime[$model->alias]['bindedModel']->alias
                                 );
        }

        return $data;
    }

    /**
     * Call user function
     *
     * @param $model Model
     * @param $function mixed The callable function name
     * @param $args array The arguments array
     * @return mixed
     * @access protected
     */
    protected function _userfunc(Model $model, $function, $args = array()) {
        if (is_array($function) && count($function) > 1) {
            list($class, $method) = $function;

            if (is_callable(array($class, $method))) {
                if (is_object($class) && is_a('Object', $class)) {
                    return $class->dispatchMethod($method, $args);

                } else {
                    return call_user_func_array(array($class, $method), $args);
                }
            }

        } else if (is_string($function)) {
            if (function_exists($function)) {
                return call_user_func_array($function, $args);

            } else if (method_exists($model, $function)) {
                return $model->dispatchMethod($function, $args);
            }
        }

        return false;
    }

    /**
     * Bind file fields
     *
     * @param $model
     * @param $data The
     */
    protected function bindFile(Model $model, $data = array()) {
        $modelName = $model->alias;
        if (empty($model->bindFields) || empty($data)) {
            return $data;
        }

        // Detect $data array format
        if (isset($data[$model->alias][$model->primaryKey])) {
            $tmpData = array($data);

        } else if (isset($data[$model->alias][0][$model->primaryKey])) {
            foreach ($data[$model->alias] as $i => $_data) {
                $tmpData[$i] = array($model->alias => $_data);
            }

        } else if (isset($data[$model->primaryKey])) {
            $tmpData = array(array($model->alias => $data));

        } else if (isset($data[0][$model->primaryKey])) {
            foreach ($data as $i => $_data) {
                $tmpData[$i] = array($model->alias => $_data);
            }

        } else {
            $tmpData = $data;
        }

        $bindFields = empty($this->bindFields) ? Set::combine($model->bindFields, '/field' , '/') : $this->bindFields;
        $model_ids = Set::extract('/' . $modelName . '/' . $this->runtime[$model->alias]['primaryKey'], $tmpData);

        if (!$model_ids) {
            return $data;
        }

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
        if ($this->settings[$model->alias]['withObject']) {
            $query['fields'][] = 'file_object';
        }

        $query['recursive'] = -1;
        $query['conditions'] = array('model' => $modelName,
                                     'model_id' => $model_ids);

        // Mongodb.MondodbSource Support
        $dataSource = $this->runtime[$model->alias]['bindedModel']->getDataSource();
        if ($dataSource->config['datasource'] === 'Mongodb.MongodbSource') {
            $query['conditions'] = array('model' => $modelName,
                                         'model_id' => array('$in' => $model_ids));
        }

        $binds = $this->runtime[$model->alias]['bindedModel']->find('all', $query);
        $binds = Set::combine($binds, array('%1$s.%2$s' , '/' . $this->settings[$model->alias]['model'] . '/model_id', '/' . $this->settings[$model->alias]['model'] . '/field_name'), '/' . $this->settings[$model->alias]['model']);
        foreach ($tmpData as $key => $value) {
            if (empty($tmpData[$key][$modelName])) {
                continue;
            }
            $model_id = $value[$modelName][$this->runtime[$model->alias]['primaryKey']];
            foreach ($bindFields as $fieldName => $bindValue) {
                if (array_key_exists($model_id . '.' . $fieldName, $binds)) {
                    $bind = $binds[$model_id . '.' . $fieldName][$this->settings[$model->alias]['model']];
                    $baseDir = empty($bindFields[$fieldName]['filePath']) ? $this->settings[$model->alias]['filePath'] : $bindFields[$fieldName]['filePath'];
                    if ($baseDir) {
                        $filePath = $baseDir . $model->transferTo(array_diff_key($bind, Set::normalize(array('file_object'))));
                    } else {
                        $filePath = false;
                    }
                    $bind['file_path'] = $filePath;
                    $bind['bindedModel'] = $this->runtime[$model->alias]['bindedModel']->alias;
                    $tmpData[$key][$modelName][$fieldName] = $bind;

                    if ((!file_exists($filePath) || filemtime($filePath) < strtotime($bind['modified']))) {

                        /**
                         * Database storage
                         */
                        $dbStorage = in_array(BindableBehavior::STORAGE_DB, (array)$this->settings[$model->alias]['storage']);
                        // backward compatible
                        if (isset($this->settings[$model->alias]['dbStorage'])) {
                            $dbStorage = $this->settings[$model->alias]['dbStorage'];
                        }
                        if ($filePath && $dbStorage) {

                            /**
                             * create entity from record data
                             */
                            if ($this->settings[$model->alias]['withObject']) {
                                $fileObject = $bind['file_object'];
                            } else {
                                $all = $this->runtime[$model->alias]['bindedModel']->findById($bind['id']);
                                $fileObject = $all[$this->settings[$model->alias]['model']]['file_object'];
                            }

                            if (!$fileObject) {
                                continue;
                            }

                            if (!is_dir(dirname($filePath))) {
                                mkdir(dirname($filePath), $this->settings[$model->alias]['dirMode'], true);
                            }

                            if (file_exists($filePath) && is_file($filePath)) {
                                @unlink($filePath);
                            }

                            if (
                                !file_put_contents($filePath, base64_decode($fileObject))
                                || !chmod($filePath, $this->settings[$model->alias]['fileMode'])
                                ) {
                                return false;
                            }
                        }

                        /**
                         * S3 storage
                         */
                        $s3Storage = in_array(BindableBehavior::STORAGE_S3, (array)$this->settings[$model->alias]['storage']);
                        if ($filePath && $s3Storage) {
                            if (!class_exists('AmazonS3') || !Configure::read('Filebinder.S3.key') || !Configure::read('Filebinder.S3.secret')) {
                                //__('Validation Error: S3 Parameter Error');
                                return false;
                            }
                            $options = array('key' => Configure::read('Filebinder.S3.key'),
                                             'secret' => Configure::read('Filebinder.S3.secret'),
                                             );
                            $bucket = !empty($bindFields[$fieldName]['bucket']) ? $bindFields[$fieldName]['bucket'] : Configure::read('Filebinder.S3.bucket');
                            if (empty($bucket)) {
                                //__('Validation Error: S3 Parameter Error');
                                return false;
                            }
                            $s3 = new AmazonS3($options);
                            $region = !empty($bindFields[$fieldName]['region']) ? $bindFields[$fieldName]['region'] : Configure::read('Filebinder.S3.region');
                            if (!empty($region)) {
                                $s3->set_region($region);
                            }
                            $responce = $s3->get_object($bucket,
                                                        $model->transferTo(array_diff_key($bind, Set::normalize(array('file_object')))),
                                                        array(
                                                              'fileDownload' => $filePath,
                                                              ));
                            if (!$responce->isOK()) {
                                //__('Validation Error: S3 Upload Error');
                                return false;
                            }
                        }

                        if (file_exists($filePath) && is_file($filePath)) {
                            /**
                             * afterAttach
                             */
                            if (!empty($this->settings[$model->alias]['afterAttach'])) {
                                $res = $this->_userfunc($model, $this->settings[$model->alias]['afterAttach'], array($filePath));

                                if (!$res) {
                                    return false;
                                }
                            }
                        }
                    }

                    /**
                     * withObject support
                     */
                    if ($this->settings[$model->alias]['withObject'] && empty($tmpData[$key][$modelName][$fieldName]['file_object'])) {
                        /**
                         * S3 storage
                         */
                        $s3Storage = in_array(BindableBehavior::STORAGE_S3, (array)$this->settings[$model->alias]['storage']);
                        if ($s3Storage) {
                            if (!class_exists('AmazonS3') || !Configure::read('Filebinder.S3.key') || !Configure::read('Filebinder.S3.secret')) {
                                //__('Validation Error: S3 Parameter Error');
                                return false;
                            }
                            $options = array('key' => Configure::read('Filebinder.S3.key'),
                                             'secret' => Configure::read('Filebinder.S3.secret'),
                                             );
                            $bucket = !empty($bindFields[$fieldName]['bucket']) ? $bindFields[$fieldName]['bucket'] : Configure::read('Filebinder.S3.bucket');
                            if (empty($bucket)) {
                                //__('Validation Error: S3 Parameter Error');
                                return false;
                            }
                            $s3 = new AmazonS3($options);
                            $region = !empty($bindFields[$fieldName]['region']) ? $bindFields[$fieldName]['region'] : Configure::read('Filebinder.S3.region');
                            if (!empty($region)) {
                                $s3->set_region($region);
                            }
                            $tmpFilePath = '/tmp/' . sha1(uniqid('', true));
                            $responce = $s3->get_object($bucket,
                                                        $model->transferTo(array_diff_key($bind, Set::normalize(array('file_object')))),
                                                        array(
                                                              'fileDownload' => $tmpFilePath,
                                                              ));
                            if (!$responce->isOK()) {
                                //__('Validation Error: S3 Upload Error');
                                return false;
                            }
                            $tmpData[$key][$modelName][$fieldName]['file_object'] = base64_encode(file_get_contents($tmpFilePath));
                        }
                    }

                } else {
                    $tmpData[$key][$modelName][$fieldName] = null;
                }
            }
        }

        // Update $data array
        if (isset($data[$model->alias][$model->primaryKey])) {
            $data[$model->alias] = $tmpData[0][$model->alias];

        } else if (isset($data[$model->alias][0][$model->primaryKey])) {
            $data[$model->alias] = Set::extract($tmpData, '{n}.' . $model->alias);

        } else if (isset($data[$model->primaryKey])) {
            $data = $tmpData[0][$model->alias];

        } else if (isset($data[0][$model->primaryKey])) {
            $data = Set::extract($tmpData, '{n}.' . $model->alias);

        } else {
            $data = $tmpData;
        }

        return $data;
    }
}
