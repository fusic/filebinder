<?php
App::uses('AppHelper', 'View/Helper');

class LabelHelper extends AppHelper {

    public $helpers = array('Html', 'Session');

    public $settings = array(
        'sessionKey' => 'Filebinder',
    );

    /**
     * image
     *
     * @param $file
     * @return
     */
    public function image($file = null, $options = array()){
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
    public function link($file = null, $options = array()){
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
    public function url($file = null, $options = array()){
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
    public function _makeSrc($file = null, $options = array()){
        $secret = $this->Session->read($this->settings['sessionKey'] . '.secret');
        $prefix = empty($options['prefix']) ? '' : $options['prefix'];

        /**
         * S3 url
         */
        if (!empty($options['S3']) || !empty($options['s3'])) {
            return $this->_makeS3Url($file, $options);
        }

        $filePath = empty($file['file_path']) ? (empty($file['tmp_bind_path']) ? false : $file['tmp_bind_path']) : preg_replace('#/([^/]+)$#' , '/' . $prefix . '$1' , $file['file_path']);
        if (empty($file) || !$filePath) {
            return false;
        }

        if (preg_match('#' . WWW_ROOT . '#', $filePath)) {
            $src = preg_replace('#' . WWW_ROOT . '#', DS, $filePath);
            return $src;
        }

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

        $expire = Configure::read('Filebinder.expire') ? strtotime(Configure::read('Filebinder.expire')) : strtotime('+1 minute');

        $key = Security::hash($file['model'] . $file['model_id'] . $file['field_name'] . $secret . $expire);

        $url = array_merge($url, array(
                'plugin' => 'filebinder',
                'controller' => 'filebinder',
                'action' => 'loader',
                $file['model'],
                $file['model_id'],
                $file['field_name'],
                $prefix . $file['file_name'],
                '?' => array('key' => $key, 'expire' => $expire),
            ));

        return $url;
    }

    /**
     * _makeS3Url
     *
     */
    public function _makeS3Url($file, $options){
        if (empty($file['model'])) {
            return null;
        }
        $prefix = empty($options['prefix']) ? '' : $options['prefix'];
        $urlPrefix = !empty($options['url_prefix']) ? $options['url_prefix'] : Configure::read('Filebinder.S3.urlPrefix');
        $http = empty($options['ssl']) ? 'http' : 'https';
        return $http . '://' . Configure::read('Filebinder.S3.bucket') . '.s3.amazonaws.com/' . $urlPrefix . $file['model'] . '/' . $file['model_id'] . '/' . $file['field_name'] . '/' . $prefix . $file['file_name'];
    }
}
