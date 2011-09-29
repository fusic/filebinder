<?php
App::uses('AppHelper', 'View/Helper');

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
        $src = $this->_makeSrc($file, $options);
        if (!$src) {
            return empty($options['noFile']) ? '' : $options['noFile'];
        }
        $fileTitle = empty($options['title']) ? $file['file_name'] : $options['title'];
        unset($options['title']);
        return $this->Html->link($fileTitle, $src, $options);
    }

    /**
     * url
     *
     * @param
     * @return
     */
    function url($file = null, $options = array()){
        $src = $this->_makeSrc($file, $options);
        if (!$src) {
            return empty($options['noFile']) ? '' : $options['noFile'];
        }
        return $this->Html->url($src, $options);
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
        if (empty($file) || !$filePath) {
            return false;
        }
        if (!preg_match('#' . WWW_ROOT . '#', $filePath)) {
            if (!empty($file['tmp_bind_path'])) {
                if (empty($file['model_id']) || file_exists($file['tmp_bind_path'])) {
                    $file['model_id'] = 0;
                    $file['file_name'] = preg_replace('#.+/([^/]+)$#' , '$1' , $file['tmp_bind_path']);
                }
            }

            // over 1.3
            $prefixes = Configure::read('Routing.prefixes');

            if (!$prefixes && Configure::read('Routing.admin')) {
                $prefixes = Configure::read('Routing.admin');
            }

            $url = array();

            foreach ((array)$prefixes as $p) {
                $url[$p] = false;
            }

            $url = array_merge($url, array(
                 'plugin' => 'filebinder',
                 'controller' => 'filebinder',
                 'action' => 'loader',
                 $file['model'],
                 $file['model_id'],
                 $file['field_name'],
                 Security::hash($file['model'] . $file['model_id'] . $file['field_name'] . $hash),
                 $prefix . $file['file_name']
            ));

            return $url;
        }
        $src = preg_replace('#' . WWW_ROOT . '#', DS, $filePath);
        return $src;
    }

}
