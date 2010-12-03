<?php
class LabelHelper extends AppHelper {

    var $helpers = array('Html', 'Session');

    /**
     * image
     *
     * @param $file
     * @return
     */
    function image($file = null, $options = array()){
        if (!$this->_makeSrc($file, $options)) {
            return empty($options['noFile']) ? '' : $options['noFile'];
        }
        return $this->Html->image($this->_makeSrc($file, $options), $options);
    }

    /**
     * link
     *
     * $param $file
     * @return
     */
    function link($file = null, $options = array()){
        if (!$this->_makeSrc($file, $options)) {
            return empty($options['noFile']) ? '' : $options['noFile'];
        }
        $fileTitle = empty($options['title']) ? $file['file_name'] : $options['title'];
        unset($options['title']);
        return $this->Html->link($fileTitle, $this->_makeSrc($file, $options), $options);
    }

    /**
     * url
     *
     * @param
     * @return
     */
    function url($file = null, $options = array()){
        if (!$this->_makeSrc($file, $options)) {
            return empty($options['noFile']) ? '' : $options['noFile'];
        }
        return $this->Html->url($this->_makeSrc($file, $options), $options);
    }

    /**
     * _makeSrc
     *
     * @param $file
     * @param $options
     * @return
     */
    function _makeSrc($file = null, $options = array()){
        $hash = $this->Session->read('Filebinder.hash');
        $prefix = empty($options['prefix']) ? '' : $options['prefix'];
        $filePath = empty($file['file_path']) ? (empty($file['tmp_bind_path']) ? false : $file['tmp_bind_path']) : preg_replace('#/([^/]+)$#' , '/' . $prefix . '$1' , $file['file_path']);
        if (!empty($file) || !$filePath) {
            return false;
        }
        if (!preg_match('#' . WWW_ROOT . '#', $filePath)) {
            return array('admin' => false,
                         'plugin' => 'filebinder',
                         'controller' => 'filebinder',
                         'action' => 'loader',
                         $file['model'],
                         $file['model_id'],
                         $file['field_name'],
                         Security::hash($file['model'] . $file['model_id'] . $file['field_name'] . $hash),
                         $prefix . $file['file_name']);
        }
        $src = preg_replace('#' . WWW_ROOT . '#', '../', $filePath);
        unset($options['noImage']);
        return $src;
    }

  }