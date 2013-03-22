<?php
App::uses('Security', 'Utility');

class FilebinderController extends FilebinderAppController {

    var $name = 'Filebinder';
    var $uses = array();
    var $components = array('Session');
    var $noUpdateHash = true;

    /**
     * loader
     * file loader
     *
     * @param string $model
     * @param string $model_id
     * @param string $fieldName
     * @param string $hash
     * @return
     */
    function loader($model = null, $model_id = null, $fieldName = null, $hash = null, $fileName = null){
        $this->layout = false;
        $this->autoRender = false;
        Configure::write('debug', 0);

        if (!$model || $model_id == null || !$fieldName || !$hash) {
            throw new NotFoundException(__('Invalid access'));
            return;
        }
        if (Security::hash($model . $model_id . $fieldName . $this->Session->read('Filebinder.hash')) !== $hash) {
            throw new NotFoundException(__('Invalid access'));
            return;
        }

        $this->loadModel($model);

        if ($model_id == 0) {
            // tmp file
            $tmpPath = CACHE;
            if (!empty($this->{$model}->bindFields)) {
                foreach ($this->{$model}->bindFields as $value) {
                    if ($value['field'] === $fieldName && !empty($value['tmpPath'])) {
                        $tmpPath = $value['tmpPath'];
                    }
                }
            }
            $filePath = $tmpPath . $fileName;
        } else {
            $query = array();
            $query['recursive'] = -1;
            $query['fields'] = array($this->{$model}->primaryKey,
                                     $fieldName);
            $query['conditions'] = array($this->{$model}->primaryKey => $model_id);
            $file = $this->{$model}->find('first', $query);

            if (empty($fileName)) {
                $fileName = $file[$model][$fieldName]['file_name'];
            }
            $fileContentType = $file[$model][$fieldName]['file_content_type'];
            $filePath = $file[$model][$fieldName]['file_path'];
        }

        if (!file_exists($filePath)) {
            throw new NotFoundException(__('Invalid access'));
            return;
        }
        if (strstr(env('HTTP_USER_AGENT'), 'MSIE')) {
            $fileName = mb_convert_encoding($fileName,  "SJIS", "UTF-8");
            header('Content-Disposition: inline; filename="'. $fileName .'"');
        } else {
            header('Content-Disposition: attachment; filename="'. $fileName .'"');
        }

        header('Content-Length: '. filesize($filePath));
        if (!empty($fileContentType)) {
            header('Content-Type: ' . $fileContentType);
        } else if (class_exists('FInfo')) {
            $info  =  new FInfo(FILEINFO_MIME_TYPE);
            $fileContentType = $info->file($filePath);
            header('Content-Type: ' . $fileContentType);
        } else if (function_exists('mime_content_type')) {
            $fileContentType = mime_content_type($filePath);
            header('Content-Type: ' . $fileContentType);
        }

        ob_end_clean(); // clean
        readfile($filePath);
    }

  }
