<?php
App::uses('View', 'View');
App::uses('SessionHelper', 'View/Helper');
App::uses('LabelHelper', 'Filebinder.View/Helper');

class LabelHelperTest extends CakeTestCase {

    public function setUp() {
        parent::setUp();
        $controller = null;
        $this->View = new View($controller);
        $this->Label = new LabelHelper($this->View);
        $this->Label->Session = new SessionHelper($this->View);
    }

    /**
     * tearDown
     *
     * @return
     */
    public function tearDown(){
        unset($this->View, $this->Label);
    }

    /**
     * test_makeSrc
     *
     * @return
     */
    public function test_makeSrc(){
        $file = array(
            'file_path' => WWW_ROOT . 'files/FilebinderPost/1/logo/logo.png',
            'model' => 'FilebinderPost',
            'model_id' => '1',
            'field_name' => 'logo',
            'file_name' => 'logo.png'
        );
        $result = $this->Label->_makeSrc($file);
        $this->assertIdentical($result, '/files/FilebinderPost/1/logo/logo.png');
    }
}
