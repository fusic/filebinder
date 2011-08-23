<?php

App::import('Core', 'Model');
App::import('Model', array('FilebinderPost'));
App::import('Fixture', 'FilebinderPost');

class FilebinderPost extends CakeTestModel{

    public $name = 'FilebinderPost';

    public $actsAs = array('Filebinder.Bindable');
}

class BindableTestCase extends CakeTestCase{

    public $fixtures = array('plugin.filebinder.attachment',
                             'plugin.filebinder.filebinder_post');

    function startTest() {
        $this->FilebinderPost = ClassRegistry::init('FilebinderPost');
        $this->FilebinderPostFixture = ClassRegistry::init('FilebinderPostFixture');
    }

    function endTest() {
        unset($this->FilebinderPost);
        unset($this->FilebinderPostFixture);
    }

    /**
     * testFind
     *
     * @return
     */
    function testFind(){
        $filePath = TMP . 'tests' . DS;
        $this->FilebinderPost->bindFields = array(
                                                  array('field' => 'logo',
                                                        'tmpPath'  => CACHE,
                                                        'filePath' => $filePath,
                                                        ),
                                                  );

        $query = array();
        $query['conditions'] = array('FilebinderPost.id' => 1);
        $result = $this->FilebinderPost->find('first', $query);

        $expected = array(
                          'id' => 1,
                          'title' => 'Title',
                          'body' => 'Filebinder.Bindable Test',
                          'created' => '2011-08-23 17:44:58',
                          'modified' => '2011-08-23 12:05:02',
                          'logo' => array(
                                          'id' => 100,
                                          'model' => 'FilebinderPost',
                                          'model_id' => 1,
                                          'field_name' => 'logo',
                                          'file_name' => 'logo.png',
                                          'file_content_type' => 'image/png',
                                          'file_size' => '771311',
                                          'created' => '2011-08-22 19:29:32',
                                          'modified' => '2011-08-22 19:29:32',
                                          'file_path' => $filePath . 'FilebinderPost/1/logo/logo.png',
                                          'bindedModel' => 'Attachment'
                                          ),
                          );

        $this->assertEqual($result['FilebinderPost'], $expected);
    }

    /**
     * testFindNoAttachment
     *
     * @return
     */
    function testFindNoAttachment(){
        $filePath = TMP . 'tests' . DS;
        $this->FilebinderPost->bindFields = array(
                                                  array('field' => 'logo',
                                                        'tmpPath'  => CACHE,
                                                        'filePath' => $filePath,
                                                        ),
                                                  );

        $query = array();
        $query['conditions'] = array('FilebinderPost.id' => 401);
        $result = $this->FilebinderPost->find('first', $query);

        $expected = array(
                          'id' => 401,
                          'title' => 'No Attachment',
                          'body' => 'Filebinder.Bindable Test',
                          'created' => '2011-08-23 17:44:58',
                          'modified' => '2011-08-23 12:05:02',
                          'logo' => null
                          );

        $this->assertEqual($result['FilebinderPost'], $expected);
    }

    /**
     * _setTestFile
     *
     * @return
     */
    function _setTestFile($to = null){
        if (!$to) {
            return false;
        }
        $from = APP . 'plugins/filebinder/tests/files/test.png';
        return copy($from, $to);
    }
}