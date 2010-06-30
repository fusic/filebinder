<?php
class LabelHelper extends AppHelper {

    var $helpers = array('Html');

    /**
     * image
     *
     * @param $filePath
     * @return
     */
    function image($filePath = null, $noImage = null){
        if (!preg_match('#' . WWW_ROOT . '#', $filePath)) {
            return $noImage;
        }
        $src = preg_replace('#' . WWW_ROOT . '#', '../', $filePath);
        return $this->Html->image($src);
    }

  }