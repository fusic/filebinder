<?php

App::import('Core', array('View', 'Controller'));
App::import('Helper', array('Session', 'Filebinder.Label'));

class LabelTest extends CakeTestCase {

    /**
     * startTest
     *
     * @return
     */
    function startTest(){
        $this->Label = new LabelHelper();
        $this->Label->Session = new SessionHelper();
    }

    /**
     * endTest
     *
     * @return
     */
    function endTest(){
        unset($this->Label);
    }

    /**
     * test_makeSrc
     *
     * @return
     */
    function test_makeSrc(){
        $file = array('file_path' => WWW_ROOT . 'files/FilebinderPost/1/logo/logo.png',
                      'model' => 'FilebinderPost',
                      'model_id' => '1',
                      'field_name' => 'logo',
                      'file_name' => 'logo.png');
        $result = $this->Label->_makeSrc($file);
        $this->assertIdentical($result, '/file/FilebinderPost/1/logo/logo.png');
    }
}