<?php
class BindableBehavior extends ModelBehavior {

    var $settings = array();

    /**
     * setup
     *
     * @param &$model
     * @param $settings
     */
    function setup(&$model, $settings = array()){
        $defaults = array('model' => 'Attachment', // attach model
                          'filePath' => WWW_ROOT . 'bind' . DS, // default attached file path
                          );

        // Merge settings
        $this->settings = Set::merge($defaults, $settings);

        // Bind model
        $model->bindModel(array('hasMany' => array($this->settings['model'] => array(
                                                                                     'className' => $this->settings['model'],
                                                                                     'foreignKey' => 'model_id',
                                                                                     'dependent' => true,
                                                                                     'conditions' => array($this->settings['model'] . '.model' => $model->name)
                                                                                     ))), false);
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
     * afterFind
     *
     * @param &$model, $result
     * @return
     */
    function afterFind(&$model, $result){
        // TODO: format $result
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

            // TODO: file object enable/disable param
            $tmpFile = $value['tmp_bind_path'];
            $fp = fopen($tmpFile, 'r');
            $ofile = fread($fp, filesize($tmpFile));
            fclose($fp);

            $bind['file_object'] = base64_encode($ofile);

            $model->{$this->settings['model']}->create();
            if (!$model->{$this->settings['model']}->save($bind)) {
                return false;
            }

            $bind_id = $model->{$this->settings['model']}->getLastInsertId();
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
            if (empty($value)) {
                continue;
            }

            $bind_id = $value['bind_id'];
            $tmpFile = $value['tmp_bind_path'];

            $bind = array();
            $bind['id'] = $bind_id;
            $bind['model_id'] = $model_id;

            $model->{$this->settings['model']}->create();
            if (!$model->{$this->settings['model']}->save($bind)) {
                return false;
            }

            $filePath = empty($bindFields[$fieldName]['filePath']) ? $this->settings['filePath'] : $bindFields[$fieldName]['filePath'];
            $bindDir = $filePath . $value['model'] . DS . $model_id . DS . $fieldName . DS;
            if (file_exists($tmpFile)) {
                if (!file_exists($bindDir)) {
                    mkdir($bindDir, 0755, true);
                }

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
        // TODO: delete attached file and records
    }
  }
?>