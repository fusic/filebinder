<?php

App::import('Controller', 'Controller', false);
App::import('Component', 'Filebinder.Ring');

if (!class_exists('FilebinderPost')) {
    class FilebinderPost extends CakeTestModel{

        public $name = 'FilebinderPost';

        public $actsAs = array('Filebinder.Bindable');
    }
}

class FilebinderPostsTestController extends Controller{

    public $name = 'FilebinderPostsTest';

    public $uses = array('FilebinderPost');

    public $components = array('Session', 'Filebinder.Ring');

    /**
     * redirect
     *
     * @param $url, $status = null, $exit = true
     * @return
     */
    public function redirect($url, $status = null, $exit = true){
        $this->redirectUrl = $url;
    }
}

class RingComponentTest extends CakeTestCase{

    public $fixtures = array('plugin.filebinder.attachment',
                             'plugin.filebinder.filebinder_post');

    function startTest() {
        $this->Controller = new FilebinderPostsTestController();
        $this->Controller->constructClasses();
        $this->Controller->params = array(
                                          'named' => array(),
                                          'pass' => array(),
                                          'url' => array());
    }

    function endTest() {
        $this->Controller->Session->delete('Filebinder');
        unset($this->Controller);
        ClassRegistry::flush();
    }

    /**
     * testBindUp
     *
     * en:
     * jpn: $this->dataが存在する場合にRing::bindUp()を実行するとアップロードされたファイル情報が整形される
     */
    function testBindUp(){
        $tmpPath = TMP . 'tests' . DS . 'bindup.png';

        // set test.png
        $this->_setTestFile($tmpPath);

        $this->Controller->FilebinderPost->bindFields = array(
                                                              array('field' => 'logo',
                                                                    'tmpPath'  => CACHE,
                                                                    'filePath' => TMP . 'tests' . DS,
                                                                    ),
                                                              );

        $this->Controller->data = array('FilebinderPost' => array());
        $this->Controller->data['FilebinderPost']['title'] = 'Title';
        $this->Controller->data['FilebinderPost']['logo'] = array('name' => 'logo.png',
                                                                  'tmp_name' => $tmpPath,
                                                                  'type' => 'image/png',
                                                                  'size' => 100,
                                                                  'error' => 0);

        $this->Controller->Component->init($this->Controller);
        $this->Controller->Component->initialize($this->Controller);
        $this->Controller->beforeFilter();

        $this->Controller->Ring->bindUp();

        $this->assertIdentical($this->Controller->data['FilebinderPost']['logo']['model'], 'FilebinderPost');
    }

    /**
     * testBindUpInvalidUploadedFile
     *
     * en: test Ring::_checkFileUploaded
     * jpn: $this->dataのファイルアップロードの値(キー)が不正な場合は該当フィールドの値にnullがセットされる
     */
    function testBindUpInvalidUploadedFile(){
        $tmpPath = TMP . 'tests' . DS . 'bindup.png';

        // set test.png
        $this->_setTestFile($tmpPath);

        $this->Controller->FilebinderPost->bindFields = array(
                                                              array('field' => 'logo',
                                                                    'tmpPath'  => CACHE,
                                                                    'filePath' => TMP . 'tests' . DS,
                                                                    ),
                                                              );

        $this->Controller->data = array('FilebinderPost' => array());
        $this->Controller->data['FilebinderPost']['title'] = 'Title';
        $this->Controller->data['FilebinderPost']['logo'] = array('name' => 'logo.png',
                                                                  'tmp_name' => $tmpPath,
                                                                  'invalid_key' => 'invalid', // invalid field
                                                                  'size' => 100,
                                                                  'error' => 0);

        $this->Controller->Component->init($this->Controller);
        $this->Controller->Component->initialize($this->Controller);
        $this->Controller->beforeFilter();
        $this->Controller->Ring->bindUp();
        $expected = array('FilebinderPost' => array('title' => 'Title',
                                                    'logo' => null));

        $this->assertIdentical($this->Controller->data, $expected);
    }

    /**
     * testBindUp_move_uploaded_file
     *
     * en:
     * jpn: テストケースで生成した$this->dataはダミーなのでmove_uploaded_file()はfalseなのでtmp_bind_pathにファイルは生成されない
     */
    function testBindUp_move_uploaded_file(){
        $tmpPath = TMP . 'tests' . DS . 'bindup.png';

        // set test.png
        $this->_setTestFile($tmpPath);

        $this->Controller->FilebinderPost->bindFields = array(
                                                              array('field' => 'logo',
                                                                    'tmpPath'  => CACHE,
                                                                    'filePath' => TMP . 'tests' . DS,
                                                                    ),
                                                              );

        $this->Controller->data = array('FilebinderPost' => array());
        $this->Controller->data['FilebinderPost']['title'] = 'Title';
        $this->Controller->data['FilebinderPost']['logo'] = array('name' => 'logo.png',
                                                                  'tmp_name' => $tmpPath,
                                                                  'type' => 'image/png',
                                                                  'size' => 100,
                                                                  'error' => 0);

        $this->Controller->Component->init($this->Controller);
        $this->Controller->Component->initialize($this->Controller);
        $this->Controller->beforeFilter();

        $this->Controller->Ring->bindUp();

        // test.png is not uploaded file.
        $this->assertIdentical(file_exists($this->Controller->data['FilebinderPost']['logo']['tmp_bind_path']), false);
    }

    /**
     * test_bindDown
     *
     * en:
     * jpn: Ring::bindDown()を実行するとアップロードファイル情報がSessionに保持される
     */
    function test_bindDown(){
        $tmpPath = TMP . 'tests' . DS . 'binddown.png';

        // set test.png
        $this->_setTestFile($tmpPath);

        $this->Controller->FilebinderPost->bindFields = array(
                                                              array('field' => 'logo',
                                                                    'tmpPath'  => CACHE,
                                                                    'filePath' => TMP . 'tests' . DS,
                                                                    ),
                                                              );

        $this->Controller->data = array('FilebinderPost' => array());
        $this->Controller->data['FilebinderPost']['title'] = 'Title';
        $this->Controller->data['FilebinderPost']['logo'] = array('name' => 'logo.png',
                                                                  'tmp_name' => $tmpPath,
                                                                  'type' => 'image/png',
                                                                  'size' => 100,
                                                                  'error' => 0);

        $this->Controller->Component->init($this->Controller);
        $this->Controller->Component->initialize($this->Controller);
        $this->Controller->beforeFilter();

        $this->Controller->Ring->bindUp();
        $this->Controller->Ring->bindDown();

        $expected = $this->Controller->data['FilebinderPost']['logo'];
        $this->assertIdentical($this->Controller->Session->read('Filebinder.FilebinderPost.logo'), $expected);
    }

    /**
     * _setTestFile
     *
     * @return Boolean
     */
    function _setTestFile($to = null){
        if (!$to) {
            return false;
        }
        $from = dirname(__FILE__) . '/../../../tests/files/test.png';
        return copy($from, $to);
    }
}