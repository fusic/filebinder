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
        if (empty($file['file_path']) || !preg_match('#' . WWW_ROOT . '#', $file['file_path'])) {
            return $noImage;
        }
        $src = preg_replace('#' . WWW_ROOT . '#', '../', $file['file_path']);
        return $this->Html->image($src);
    }

  }