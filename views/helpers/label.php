<?php
class LabelHelper extends AppHelper {

    var $helpers = array('Html');

    /**
     * image
     *
     * @param $file
     * @return
     */
    function image($file = null, $noImage = null){
        $filePath = empty($file['file_path']) ? (empty($file['tmp_bind_path']) ? false : $file['tmp_bind_path']) : $file['file_path'];
        if (!$filePath || !preg_match('#' . WWW_ROOT . '#', $filePath)) {
            return $noImage;
        }
        $src = preg_replace('#' . WWW_ROOT . '#', '../', $filePath);
        return $this->Html->image($src);
    }

  }