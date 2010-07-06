<?php
class LabelHelper extends AppHelper {

    var $helpers = array('Html');

    /**
     * image
     *
     * @param $file
     * @return
     */
    function image($file = null, $options = array()){
        $filePath = empty($file['file_path']) ? (empty($file['tmp_bind_path']) ? false : $file['tmp_bind_path']) : $file['file_path'];
        if (!$filePath || !preg_match('#' . WWW_ROOT . '#', $filePath)) {
            return empty($options['noImage']) ? '' : $options['noImage'];
        }
        $src = preg_replace('#' . WWW_ROOT . '#', '../', $filePath);
        unset($options['noImage']);
        return $this->Html->image($src, $options);
    }

  }