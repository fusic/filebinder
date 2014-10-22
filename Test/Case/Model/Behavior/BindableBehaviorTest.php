<?php

App::uses('Model', 'Model');
App::uses('AppModel', 'Model');

class FilebinderPost extends CakeTestModel{

    public $name = 'FilebinderPost';

    public $actsAs = array('Filebinder.Bindable');
}

class BindableTestCase extends CakeTestCase{

    public $fixtures = array(
        'plugin.filebinder.attachment',
        'plugin.filebinder.filebinder_post'
    );

    public function setUp() {
        $this->FilebinderPost = new FilebinderPost(); // jpn: 初期化するため
        $this->FilebinderPostFixture = ClassRegistry::init('FilebinderPostFixture');
    }

    public function tearDown() {
        unset($this->FilebinderPost);
        unset($this->FilebinderPostFixture);
    }

    /**
     * testSettings
     *
     * jpn: BindableBehaviorの設定変更ができる
     */
    public function testSetSettings(){
        $settings = $this->FilebinderPost->getSettings();
        $settings['storage'] = false;
        $before = $settings;
        $this->FilebinderPost->setSettings($settings);
        $after = $this->FilebinderPost->getSettings();
        $this->assertIdentical($before, $after);
    }

    /**
     * testFind
     *
     * en:
     * jpn: Model::bindFieldsに設定してある対象のデータがある場合Model::find()時に整形して取得する
     */
    public function testFind(){
        $filePath = TMP . 'tests' . DS;
        $this->FilebinderPost->bindFields = array(
            array(
                'field' => 'logo',
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
     * en:
     * jpn: 対象のデータがない場合もnull値がセットされたフィールドを生成して返す
     */
    public function testFindNoAttachment(){
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
            'logo' => null // Set null
        );

        $this->assertEqual($result['FilebinderPost'], $expected);
    }

    /**
     * testSave
     *
     * en:
     * jpn: Ring::bindUp()で整形されたデータをModel::save()したときModel::find()で正常に取得できる
     */
    public function testSave(){
        $tmpPath = TMP . 'tests' . DS . 'bindup.png';
        $filePath = TMP . 'tests' . DS;

        // set test.png
        $this->_setTestFile($tmpPath);

        $this->FilebinderPost->bindFields = array(
            array(
                'field' => 'logo',
                'tmpPath'  => CACHE,
                'filePath' => $filePath,
            ),
        );

        $data = array(
            'FilebinderPost' => array(
                'title' => 'Title',
                'logo' => array(
                    'model' => 'FilebinderPost',
                    'field_name' => 'logo',
                    'file_name' => 'logo.png',
                    'file_content_type' => 'image/png',
                    'file_size' => 1395,
                    'tmp_bind_path' => $tmpPath
                )));
        $result = $this->FilebinderPost->save($data);
        $id = $this->FilebinderPost->getLastInsertId();
        $query = array();
        $query['conditions'] = array('FilebinderPost.id' => $id);
        $result = $this->FilebinderPost->find('first', $query);

        $this->assertIdentical(file_exists($result['FilebinderPost']['logo']['file_path']), true);

        // rm file
        if (file_exists($result['FilebinderPost']['logo']['file_path'])) {
            unlink($result['FilebinderPost']['logo']['file_path']);
        }
    }

    /**
     * testFindWithObject
     *
     * en:
     * jpn: withObject設定をtrueをすると実データを取得する
     */
    public function testFindWithObject(){
        $tmpPath = TMP . 'tests' . DS . 'bindup.png';
        $filePath = TMP . 'tests' . DS;

        // change settings
        $settings = $this->FilebinderPost->getSettings();
        $settings['withObject'] = true;
        $this->FilebinderPost->setSettings($settings);

        // set test.png
        $this->_setTestFile($tmpPath);

        $this->FilebinderPost->bindFields = array(
            array(
                'field' => 'logo',
                'tmpPath'  => CACHE,
                'filePath' => $filePath,
            ),
        );

        $data = array(
            'FilebinderPost' => array(
                'title' => 'Title',
                'logo' => array(
                    'model' => 'FilebinderPost',
                    'field_name' => 'logo',
                    'file_name' => 'logo.png',
                    'file_content_type' => 'image/png',
                    'file_size' => 1395,
                    'tmp_bind_path' => $tmpPath
                )));
        $result = $this->FilebinderPost->save($data);
        $id = $this->FilebinderPost->getLastInsertId();
        $query = array();
        $query['conditions'] = array('FilebinderPost.id' => $id);
        $this->FilebinderPost->withObject(true);
        $result = $this->FilebinderPost->find('first', $query);

        $this->assertTrue(is_string($result['FilebinderPost']['logo']['file_object']));

        // rm file
        if (file_exists($result['FilebinderPost']['logo']['file_path'])) {
            unlink($result['FilebinderPost']['logo']['file_path']);
        }
    }

    /**
     * testSaveNoStrage
     *
     * en:
     * jpn: DBにファイルを保存しない設定のときはwithObjectのときもデータは入っていない
     */
    public function testSaveNoStrage(){
        $tmpPath = TMP . 'tests' . DS . 'bindup.png';
        $filePath = TMP . 'tests' . DS;

        // change settings
        $settings = $this->FilebinderPost->getSettings();
        $settings['storage'] = false;
        $this->FilebinderPost->setSettings($settings);

        // set test.png
        $this->_setTestFile($tmpPath);

        $this->FilebinderPost->bindFields = array(
            array(
                'field' => 'logo',
                'tmpPath'  => CACHE,
                'filePath' => $filePath,
            ),
        );

        $data = array(
            'FilebinderPost' => array(
                'title' => 'Title',
                'logo' => array('model' => 'FilebinderPost',
                                'field_name' => 'logo',
                                'file_name' => 'logo.png',
                                'file_content_type' => 'image/png',
                                'file_size' => 1395,
                                'tmp_bind_path' => $tmpPath
                )));
        $result = $this->FilebinderPost->save($data);
        $id = $this->FilebinderPost->getLastInsertId();
        $query = array();
        $query['conditions'] = array('FilebinderPost.id' => $id);
        $this->FilebinderPost->withObject(true);
        $result = $this->FilebinderPost->find('first', $query);

        $this->assertIdentical($result['FilebinderPost']['logo']['file_object'], null);

        // rm file
        if (file_exists($result['FilebinderPost']['logo']['file_path'])) {
            unlink($result['FilebinderPost']['logo']['file_path']);
        }
    }

    /**
     * testRestore
     *
     * en:
     * jpn: DBに保存したデータからローカルファイルを復旧する
     */
    public function testRestore(){
        $tmpPath = TMP . 'tests' . DS . 'bindup.png';
        $filePath = TMP . 'tests' . DS;

        // change settings
        $settings = $this->FilebinderPost->getSettings();
        $settings['storage'] = BindableBehavior::STORAGE_DB;
        $this->FilebinderPost->setSettings($settings);

        // set test.png
        $this->_setTestFile($tmpPath);

        $this->FilebinderPost->bindFields = array(
            array(
                'field' => 'logo',
                'tmpPath'  => CACHE,
                'filePath' => $filePath,
            ),
        );

        $data = array(
            'FilebinderPost' => array(
                'title' => 'Title',
                'logo' => array(
                    'model' => 'FilebinderPost',
                    'field_name' => 'logo',
                    'file_name' => 'logo.png',
                    'file_content_type' => 'image/png',
                    'file_size' => 1395,
                    'tmp_bind_path' => $tmpPath
                )));
        $result = $this->FilebinderPost->save($data);
        $id = $this->FilebinderPost->getLastInsertId();
        $query = array();
        $query['conditions'] = array('FilebinderPost.id' => $id);
        $result = $this->FilebinderPost->find('first', $query);

        // rm file
        if (file_exists($result['FilebinderPost']['logo']['file_path'])) {
            unlink($result['FilebinderPost']['logo']['file_path']);
        }

        $this->assertFalse(file_exists($result['FilebinderPost']['logo']['file_path']));

        $result = $this->FilebinderPost->find('first', $query);
        $this->assertTrue(file_exists($result['FilebinderPost']['logo']['file_path']));

        // rm file
        if (file_exists($result['FilebinderPost']['logo']['file_path'])) {
            unlink($result['FilebinderPost']['logo']['file_path']);
        }
    }

    /**
     * testRestoreFalse
     *
     * en:
     * jpn: restoreFromStorage = falseの場合はDBに保存したデータからファイルを復旧しない
     */
    public function testRestoreFalse(){
        $tmpPath = TMP . 'tests' . DS . 'bindup.png';
        $filePath = TMP . 'tests' . DS;

        // change settings
        $settings = $this->FilebinderPost->getSettings();
        $settings['storage'] = BindableBehavior::STORAGE_DB;
        $settings['restoreFromStorage'] = false;
        $this->FilebinderPost->setSettings($settings);

        // set test.png
        $this->_setTestFile($tmpPath);

        $this->FilebinderPost->bindFields = array(
            array(
                'field' => 'logo',
                'tmpPath'  => CACHE,
                'filePath' => $filePath,
            ),
        );

        $data = array(
            'FilebinderPost' => array(
                'title' => 'Title',
                'logo' => array(
                    'model' => 'FilebinderPost',
                    'field_name' => 'logo',
                    'file_name' => 'logo.png',
                    'file_content_type' => 'image/png',
                    'file_size' => 1395,
                    'tmp_bind_path' => $tmpPath
                )));
        $result = $this->FilebinderPost->save($data);
        $id = $this->FilebinderPost->getLastInsertId();
        $query = array();
        $query['conditions'] = array('FilebinderPost.id' => $id);
        $result = $this->FilebinderPost->find('first', $query);

        // rm file
        if (file_exists($result['FilebinderPost']['logo']['file_path'])) {
            unlink($result['FilebinderPost']['logo']['file_path']);
        }

        $this->assertFalse(file_exists($result['FilebinderPost']['logo']['file_path']));

        $result = $this->FilebinderPost->find('first', $query);
        $this->assertFalse(file_exists($result['FilebinderPost']['logo']['file_path']));

        // rm file
        if (file_exists($result['FilebinderPost']['logo']['file_path'])) {
            unlink($result['FilebinderPost']['logo']['file_path']);
        }
    }

    /**
     * testSaveS3
     *
     * en:
     * jpn: ファイルをS3にも保存できる
     */
    public function testSaveS3(){
        $tmpPath = TMP . 'tests' . DS . 'bindup.png';
        $filePath = TMP . 'tests' . DS;

        if(!class_exists('Aws\S3\S3Client') || !Configure::read('Filebinder.S3.bucket')) {
            return;
        }

        // change settings
        $settings = $this->FilebinderPost->getSettings();
        $settings['storage'] = array(BindableBehavior::STORAGE_DB, BindableBehavior::STORAGE_S3);
        $this->FilebinderPost->setSettings($settings);

        // set test.png
        $this->_setTestFile($tmpPath);

        // set S3 Access Key
        Configure::write('Filebinder.S3.key', AWS_ACCESS_KEY);
        Configure::write('Filebinder.S3.secret', AWS_SECRET_ACCESS_KEY);
        Configure::write('Filebinder.S3.bucket', AWS_S3_BUCKET);
        // Configure::write('Filebinder.S3.region', AmazonS3::REGION_TOKYO);

        $this->FilebinderPost->bindFields = array(
            array(
                'field' => 'logo',
                'tmpPath'  => CACHE,
                'filePath' => $filePath,
                'bucket' => AWS_S3_BUCKET,
                'acl' => Aws\S3\Enum\CannedAcl::PUBLIC_READ,
            ),
        );

        $data = array(
            'FilebinderPost' => array(
                'title' => 'Title',
                'logo' => array(
                    'model' => 'FilebinderPost',
                    'field_name' => 'logo',
                    'file_name' => 'logo.png',
                    'file_content_type' => 'image/png',
                    'file_size' => 1395,
                    'tmp_bind_path' => $tmpPath
                )));
        $result = $this->FilebinderPost->save($data);

        $id = $this->FilebinderPost->getLastInsertId();
        $query = array();
        $query['conditions'] = array('FilebinderPost.id' => $id);
        $result = $this->FilebinderPost->find('first', $query);
        $urlPrefix = Configure::read('Filebinder.S3.urlPrefix');

        $this->assertIdentical(file_get_contents($result['FilebinderPost']['logo']['file_path']),
                               file_get_contents('http://' . AWS_S3_BUCKET . '.s3.amazonaws.com/' . $urlPrefix . 'FilebinderPost/' . $result['FilebinderPost']['id'] . '/' . 'logo/' . $result['FilebinderPost']['logo']['file_name']));

        // rm file
        if (file_exists($result['FilebinderPost']['logo']['file_path'])) {
            unlink($result['FilebinderPost']['logo']['file_path']);
        }
    }

    /**
     * testRestoreS3
     *
     * en:
     * jpn: S3に保存したデータからローカルファイルを復旧する
     */
    public function testRestoreS3(){
        $tmpPath = TMP . 'tests' . DS . 'bindup.png';
        $filePath = TMP . 'tests' . DS;

        if(!class_exists('Aws\S3\S3Client') || !Configure::read('Filebinder.S3.bucket')) {
            return;
        }

        // change settings
        $settings = $this->FilebinderPost->getSettings();
        $settings['storage'] = BindableBehavior::STORAGE_S3;
        $this->FilebinderPost->setSettings($settings);

        // set test.png
        $this->_setTestFile($tmpPath);

        // set S3 Access Key
        Configure::write('Filebinder.S3.key', AWS_ACCESS_KEY);
        Configure::write('Filebinder.S3.secret', AWS_SECRET_ACCESS_KEY);

        $this->FilebinderPost->bindFields = array(
            array(
                'field' => 'logo',
                'tmpPath'  => CACHE,
                'filePath' => $filePath,
                'bucket' => AWS_S3_BUCKET,
                'acl' => Aws\S3\Enum\CannedAcl::PRIVATE_ACCESS,
            ),
        );

        $data = array(
            'FilebinderPost' => array(
                'title' => 'Title',
                'logo' => array(
                    'model' => 'FilebinderPost',
                    'field_name' => 'logo',
                    'file_name' => 'logo.png',
                    'file_content_type' => 'image/png',
                    'file_size' => 1395,
                    'tmp_bind_path' => $tmpPath
                )));
        $result = $this->FilebinderPost->save($data);
        $id = $this->FilebinderPost->getLastInsertId();
        $query = array();
        $query['conditions'] = array('FilebinderPost.id' => $id);
        $result = $this->FilebinderPost->find('first', $query);

        // rm file
        if (file_exists($result['FilebinderPost']['logo']['file_path'])) {
            unlink($result['FilebinderPost']['logo']['file_path']);
        }

        $this->assertFalse(file_exists($result['FilebinderPost']['logo']['file_path']));

        $result = $this->FilebinderPost->find('first', $query);
        $this->assertTrue(file_exists($result['FilebinderPost']['logo']['file_path']));

        // rm file
        if (file_exists($result['FilebinderPost']['logo']['file_path'])) {
            unlink($result['FilebinderPost']['logo']['file_path']);
        }
    }

    /**
     * testRestoreFalseS3
     *
     * en:
     * jpn: restoreFromStorage = falseの場合はS3に保存したファイルを復旧しない
     */
    public function testRestoreFalseS3(){
        $tmpPath = TMP . 'tests' . DS . 'bindup.png';
        $filePath = TMP . 'tests' . DS;

        if(!class_exists('Aws\S3\S3Client') || !Configure::read('Filebinder.S3.bucket')) {
            return;
        }

        // change settings
        $settings = $this->FilebinderPost->getSettings();
        $settings['storage'] = BindableBehavior::STORAGE_S3;
        $settings['restoreFromStorage'] = false;
        $this->FilebinderPost->setSettings($settings);

        // set test.png
        $this->_setTestFile($tmpPath);

        // set S3 Access Key
        Configure::write('Filebinder.S3.key', AWS_ACCESS_KEY);
        Configure::write('Filebinder.S3.secret', AWS_SECRET_ACCESS_KEY);

        $this->FilebinderPost->bindFields = array(
            array(
                'field' => 'logo',
                'tmpPath'  => CACHE,
                'filePath' => $filePath,
                'bucket' => AWS_S3_BUCKET,
                'acl' => Aws\S3\Enum\CannedAcl::PRIVATE_ACCESS,
            ),
        );

        $data = array(
            'FilebinderPost' => array(
                'title' => 'Title',
                'logo' => array(
                    'model' => 'FilebinderPost',
                    'field_name' => 'logo',
                    'file_name' => 'logo.png',
                    'file_content_type' => 'image/png',
                    'file_size' => 1395,
                    'tmp_bind_path' => $tmpPath
                )));
        $result = $this->FilebinderPost->save($data);
        $id = $this->FilebinderPost->getLastInsertId();
        $query = array();
        $query['conditions'] = array('FilebinderPost.id' => $id);
        $result = $this->FilebinderPost->find('first', $query);

        // rm file
        if (file_exists($result['FilebinderPost']['logo']['file_path'])) {
            unlink($result['FilebinderPost']['logo']['file_path']);
        }

        $this->assertFalse(file_exists($result['FilebinderPost']['logo']['file_path']));

        $result = $this->FilebinderPost->find('first', $query);
        $this->assertFalse(file_exists($result['FilebinderPost']['logo']['file_path']));

        // rm file
        if (file_exists($result['FilebinderPost']['logo']['file_path'])) {
            unlink($result['FilebinderPost']['logo']['file_path']);
        }
    }

    /**
     * testFindWithObjectS3
     *
     * jpn: S3に保存しているデータを擬似的にfile_objectに格納して取得可能
     */
    public function testFindWithObjectS3(){
        $tmpPath = TMP . 'tests' . DS . 'bindup.png';
        $filePath = TMP . 'tests' . DS;

        if(!class_exists('Aws\S3\S3Client') || !Configure::read('Filebinder.S3.bucket')) {
            return;
        }

        // change settings
        $settings = $this->FilebinderPost->getSettings();
        $settings['storage'] = BindableBehavior::STORAGE_S3;
        $settings['withObject'] = true;
        $this->FilebinderPost->setSettings($settings);

        // set test.png
        $this->_setTestFile($tmpPath);

        // set S3 Access Key
        Configure::write('Filebinder.S3.key', AWS_ACCESS_KEY);
        Configure::write('Filebinder.S3.secret', AWS_SECRET_ACCESS_KEY);

        $this->FilebinderPost->bindFields = array(
            array(
                'field' => 'logo',
                'tmpPath'  => CACHE,
                'filePath' => $filePath,
                'bucket' => AWS_S3_BUCKET,
                'acl' => 'public-read',
            ),
        );

        $data = array(
            'FilebinderPost' => array(
                'title' => 'Title',
                'logo' => array(
                    'model' => 'FilebinderPost',
                    'field_name' => 'logo',
                    'file_name' => 'logo.png',
                    'file_content_type' => 'image/png',
                    'file_size' => 1395,
                    'tmp_bind_path' => $tmpPath
                )));
        $result = $this->FilebinderPost->save($data);
        $id = $this->FilebinderPost->getLastInsertId();
        $query = array();
        $query['conditions'] = array('FilebinderPost.id' => $id);
        $result = $this->FilebinderPost->find('first', $query);

        $this->assertTrue(is_string($result['FilebinderPost']['logo']['file_object']));

        // rm file
        if (file_exists($result['FilebinderPost']['logo']['file_path'])) {
            unlink($result['FilebinderPost']['logo']['file_path']);
        }
    }

    /**
     * testSaveS3WithUrlPrefix
     *
     * en:
     * jpn: urlPrefixパラメータを付与することでファイルを保存するS3のルートディレクトリを指定出来る
     */
    public function testSaveS3WithUrlPrefix(){
        $tmpPath = TMP . 'tests' . DS . 'bindup.png';
        $filePath = TMP . 'tests' . DS;
        $urlPrefix = 'testPrefix/';

        if(!class_exists('Aws\S3\S3Client') || !Configure::read('Filebinder.S3.bucket')) {
            return;
        }

        // change settings
        $settings = $this->FilebinderPost->getSettings();
        $settings['storage'] = array(BindableBehavior::STORAGE_DB, BindableBehavior::STORAGE_S3);
        $this->FilebinderPost->setSettings($settings);

        // set test.png
        $this->_setTestFile($tmpPath);

        // set S3 Access Key
        Configure::write('Filebinder.S3.key', AWS_ACCESS_KEY);
        Configure::write('Filebinder.S3.secret', AWS_SECRET_ACCESS_KEY);
        // Configure::write('Filebinder.S3.bucket', AWS_S3_BUCKET);
        // Configure::write('Filebinder.S3.region', AmazonS3::REGION_TOKYO);

        $this->FilebinderPost->bindFields = array(
            array(
                'field' => 'logo',
                'tmpPath'  => CACHE,
                'filePath' => $filePath,
                'urlPrefix' => $urlPrefix,
                'bucket' => AWS_S3_BUCKET,
                'acl' => 'public-read',
            ),
        );

        $data = array(
            'FilebinderPost' => array(
                'title' => 'Title',
                'logo' => array(
                    'model' => 'FilebinderPost',
                    'field_name' => 'logo',
                    'file_name' => 'logo.png',
                    'file_content_type' => 'image/png',
                    'file_size' => 1395,
                    'tmp_bind_path' => $tmpPath
                )));
        $result = $this->FilebinderPost->save($data);
        $id = $this->FilebinderPost->getLastInsertId();
        $query = array();
        $query['conditions'] = array('FilebinderPost.id' => $id);
        $result = $this->FilebinderPost->find('first', $query);

        $this->assertIdentical(file_get_contents($result['FilebinderPost']['logo']['file_path']),
                               file_get_contents('http://' . AWS_S3_BUCKET . '.s3.amazonaws.com/' . $urlPrefix . 'FilebinderPost/' . $result['FilebinderPost']['id'] . '/' . 'logo/' . $result['FilebinderPost']['logo']['file_name']));

        $this->FilebinderPost->delete($id);

        // rm file
        if (file_exists($result['FilebinderPost']['logo']['file_path'])) {
            unlink($result['FilebinderPost']['logo']['file_path']);
        }
    }


    /**
     * testSaveNoLocal
     *
     * en:
     * jpn: filePath = falseにした場合、ローカルにファイルを保存しない
     */
    public function testSaveNoLocal(){
        $tmpPath = TMP . 'tests' . DS . 'bindup.png';
        $filePath = false;

        // change settings
        $settings = $this->FilebinderPost->getSettings();
        $settings['storage'] = BindableBehavior::STORAGE_DB;
        $settings['filePath'] = $filePath;
        $this->FilebinderPost->setSettings($settings);

        // set test.png
        $this->_setTestFile($tmpPath);

        $this->FilebinderPost->bindFields = array(
            array(
                'field' => 'logo',
                'tmpPath'  => CACHE,
                'filePath' => $filePath,
            ),
        );

        $data = array(
            'FilebinderPost' => array(
                'title' => 'Title',
                'logo' => array(
                    'model' => 'FilebinderPost',
                    'field_name' => 'logo',
                    'file_name' => 'logo.png',
                    'file_content_type' => 'image/png',
                    'file_size' => 1395,
                    'tmp_bind_path' => $tmpPath
                )));
        $result = $this->FilebinderPost->save($data);
        $id = $this->FilebinderPost->getLastInsertId();
        $query = array();
        $query['conditions'] = array('FilebinderPost.id' => $id);
        $this->FilebinderPost->withObject(true);
        $result = $this->FilebinderPost->find('first', $query);
        $this->assertTrue(is_string($result['FilebinderPost']['logo']['file_object']));

        $this->assertIdentical(file_exists($tmpPath), false);
        $this->assertIdentical($result['FilebinderPost']['logo']['file_path'], false);
    }

    /**
     * testDelete
     *
     * en:
     * jpn: 削除した場合もひもづく仮想フィールドも削除される。同時に実データも削除される
     */
    public function testDelete(){
        $tmpPath = TMP . 'tests' . DS . 'bindup.png';
        $filePath = TMP . 'tests' . DS;

        // set test.png
        $this->_setTestFile($tmpPath);

        $this->FilebinderPost->bindFields = array(
            array(
                'field' => 'logo',
                'tmpPath'  => CACHE,
                'filePath' => $filePath,
            ),
        );

        $data = array(
            'FilebinderPost' => array(
                'title' => 'Title',
                'logo' => array(
                    'model' => 'FilebinderPost',
                    'field_name' => 'logo',
                    'file_name' => 'logo.png',
                    'file_content_type' => 'image/png',
                    'file_size' => 1395,
                    'tmp_bind_path' => $tmpPath
                )));
        $result = $this->FilebinderPost->save($data);
        $id = $this->FilebinderPost->getLastInsertId();
        $query = array();
        $query['conditions'] = array('FilebinderPost.id' => $id);
        $result = $this->FilebinderPost->find('first', $query);

        $this->FilebinderPost->delete($id);

        $this->assertIdentical(file_exists($result['FilebinderPost']['logo']['file_path']), false);
    }

    /**
     * testDeleteS3
     *
     * en:
     * jpn: 削除した場合もひもづく仮想フィールドも削除される。同時に実データ、S3のデータも削除される
     */
    public function testDeleteS3(){
        $tmpPath = TMP . 'tests' . DS . 'bindup.png';
        $filePath = TMP . 'tests' . DS;

        if(!class_exists('Aws\S3\S3Client') || !Configure::read('Filebinder.S3.bucket')) {
            return;
        }

        // change settings
        $settings = $this->FilebinderPost->getSettings();
        $settings['storage'] = BindableBehavior::STORAGE_S3;
        $this->FilebinderPost->setSettings($settings);

        // set test.png
        $this->_setTestFile($tmpPath);

        // set S3 Access Key
        Configure::write('Filebinder.S3.key', AWS_ACCESS_KEY);
        Configure::write('Filebinder.S3.secret', AWS_SECRET_ACCESS_KEY);

        $this->FilebinderPost->bindFields = array(
            array(
                'field' => 'logo',
                'tmpPath'  => CACHE,
                'filePath' => $filePath,
                'bucket' => AWS_S3_BUCKET,
                'acl' => 'public-read',
            ),
        );

        $data = array(
            'FilebinderPost' => array(
                'title' => 'Title',
                'logo' => array(
                    'model' => 'FilebinderPost',
                    'field_name' => 'logo',
                    'file_name' => 'logo.png',
                    'file_content_type' => 'image/png',
                    'file_size' => 1395,
                    'tmp_bind_path' => $tmpPath
                )));
        $result = $this->FilebinderPost->save($data);
        $id = $this->FilebinderPost->getLastInsertId();
        $query = array();
        $query['conditions'] = array('FilebinderPost.id' => $id);
        $result = $this->FilebinderPost->find('first', $query);
        $urlPrefix = Configure::read('Filebinder.S3.urlPrefix');

        $this->assertTrue(file_exists($result['FilebinderPost']['logo']['file_path']));
        $this->assertIdentical(file_get_contents('http://' . AWS_S3_BUCKET . '.s3.amazonaws.com/' . $urlPrefix . 'FilebinderPost/' . $result['FilebinderPost']['id'] . '/' . 'logo/' . $result['FilebinderPost']['logo']['file_name']),
                               file_get_contents($result['FilebinderPost']['logo']['file_path']));

        $this->FilebinderPost->delete($id);

        $this->assertFalse(file_exists($result['FilebinderPost']['logo']['file_path']));

        $this->assertFalse(@file_get_contents('http://' . AWS_S3_BUCKET . '.s3.amazonaws.com/' . $urlPrefix . 'FilebinderPost/' . $result['FilebinderPost']['id'] . '/' . 'logo/' . $result['FilebinderPost']['logo']['file_name']));
    }

    /**
     * testDeleteNoAttachment
     *
     * en:
     * jpn: ྪファイルがアップされていない場合でも通常通り削除できる
     */
    public function testDeleteNoAttachment(){
        $filePath = TMP . 'tests' . DS;

        $this->FilebinderPost->bindFields = array(
            array(
                'field' => 'logo',
                'tmpPath'  => CACHE,
                'filePath' => $filePath,
            ),
        );

        $data = array(
            'FilebinderPost' => array(
                'title' => 'Title',
                'logo' => null
            ));
        $result = $this->FilebinderPost->save($data);
        $id = $this->FilebinderPost->getLastInsertId();
        $query = array();
        $query['conditions'] = array('FilebinderPost.id' => $id);
        $result = $this->FilebinderPost->find('first', $query);

        $result = $this->FilebinderPost->delete($id);

        $this->assertIdentical($result, true);
    }

    /**
     * testDelete_bindedFileOnly
     *
     * en:
     * jpn: 仮想フィールドにdelete_プレフィックスをつけた値を与えたとき仮想フィールドの実ファイルを削除する
     */
    public function testDelete_bindedFileOnly(){
        $tmpPath = TMP . 'tests' . DS . 'bindup.png';
        $filePath = TMP . 'tests' . DS;

        // set test.png
        $this->_setTestFile($tmpPath);

        $this->FilebinderPost->bindFields = array(
            array(
                'field' => 'logo',
                'tmpPath'  => CACHE,
                'filePath' => $filePath,
            ),
        );

        $data = array(
            'FilebinderPost' => array(
                'title' => 'Title',
                'logo' => array(
                    'model' => 'FilebinderPost',
                    'field_name' => 'logo',
                    'file_name' => 'logo.png',
                    'file_content_type' => 'image/png',
                    'file_size' => 1395,
                    'tmp_bind_path' => $tmpPath
                )));
        $result = $this->FilebinderPost->save($data);
        $id = $this->FilebinderPost->getLastInsertId();
        $query = array();
        $query['conditions'] = array('FilebinderPost.id' => $id);
        $result = $this->FilebinderPost->find('first', $query);

        $data = array(
            'FilebinderPost' => array(
                'id' => $id,
                'title' => 'Title',
                'logo' => null,
                'delete_logo' => '1',
            ));
        $this->FilebinderPost->save($data);

        $this->assertIdentical(file_exists($result['FilebinderPost']['logo']['file_path']), false);
    }

    /**
     * testNotDelete_bindedFile
     *
     * en:
     * jpn: 仮想フィールドにdelete_プレフィックスをつけた値がfalse(0)の場合は削除しない
     */
    public function testNotDelete_bindedFile(){
        $tmpPath = TMP . 'tests' . DS . 'bindup.png';
        $filePath = TMP . 'tests' . DS;

        // set test.png
        $this->_setTestFile($tmpPath);

        $this->FilebinderPost->bindFields = array(
            array(
                'field' => 'logo',
                'tmpPath'  => CACHE,
                'filePath' => $filePath,
            ),
        );

        $data = array(
            'FilebinderPost' => array(
                'title' => 'Title',
                'logo' => array(
                    'model' => 'FilebinderPost',
                    'field_name' => 'logo',
                    'file_name' => 'logo.png',
                    'file_content_type' => 'image/png',
                    'file_size' => 1395,
                    'tmp_bind_path' => $tmpPath
                )));
        $result = $this->FilebinderPost->save($data);
        $id = $this->FilebinderPost->getLastInsertId();
        $query = array();
        $query['conditions'] = array('FilebinderPost.id' => $id);
        $result = $this->FilebinderPost->find('first', $query);

        $data = array(
            'FilebinderPost' => array(
                'id' => $id,
                'title' => 'Title',
                'logo' => null,
                'delete_logo' => '0',
            ));
        $this->FilebinderPost->save($data);

        $this->assertIdentical(file_exists($result['FilebinderPost']['logo']['file_path']), true);
    }

    /**
     * testUpdateBindedFile
     *
     * en:
     * jpn: 仮想フィールドの値を更新した場合、元の実データは削除される
     */
    public function testUpdateFile(){
        $tmpPath = TMP . 'tests' . DS . 'bindup.png';
        $tmpPath2 = TMP . 'tests' . DS . 'bindup2.png';
        $filePath = TMP . 'tests' . DS;

        // set test.png
        $this->_setTestFile($tmpPath);
        $this->_setTestFile($tmpPath2);

        $this->FilebinderPost->bindFields = array(
            array(
                'field' => 'logo',
                'tmpPath'  => CACHE,
                'filePath' => $filePath,
            ),
        );

        $data = array(
            'FilebinderPost' => array(
                'title' => 'Title',
                'logo' => array(
                    'model' => 'FilebinderPost',
                    'field_name' => 'logo',
                    'file_name' => 'logo.png', // file_name:logo.png
                    'file_content_type' => 'image/png',
                    'file_size' => 1395,
                    'tmp_bind_path' => $tmpPath
                )));
        $result = $this->FilebinderPost->save($data);
        $id = $this->FilebinderPost->getLastInsertId();
        $query = array();
        $query['conditions'] = array('FilebinderPost.id' => $id);
        $result = $this->FilebinderPost->find('first', $query);

        $data = array(
            'FilebinderPost' => array(
                'id' => $id,
                'title' => 'Title',
                'logo' => array(
                    'model' => 'FilebinderPost',
                    'field_name' => 'logo',
                    'file_name' => 'logo2.png', // file_name:logo2.png
                    'file_content_type' => 'image/png',
                    'file_size' => 1395,
                    'tmp_bind_path' => $tmpPath2
                )));
        $result2 = $this->FilebinderPost->save($data);
        $this->assertIdentical(file_exists($result['FilebinderPost']['logo']['file_path']), false);
        $id = $this->FilebinderPost->getLastInsertId();
        $query = array();
        $query['conditions'] = array('FilebinderPost.id' => $id);
        $result2 = $this->FilebinderPost->find('first', $query);

        // rm file
        if (file_exists($result['FilebinderPost']['logo']['file_path'])) {
            unlink($result['FilebinderPost']['logo']['file_path']);
        }
        if (file_exists($result2['FilebinderPost']['logo']['file_path'])) {
            unlink($result2['FilebinderPost']['logo']['file_path']);
        }
    }

    /**
     * testCheckMaxFileSize
     *
     * en:
     * jpn: '1MB'指定の場合は1MBまで許可すること
     */
    public function testCheckMaxFileSize(){
        $data = array(
            'file' => array('file_size' => 1 * 1024 * 1024),
        );
        $result = $this->FilebinderPost->checkMaxFileSize($data, '1MB');
        $this->assertTrue($result);
    }

    /**
     * testCheckMinFileSize
     *
     * en:
     * jpn: '1MB'指定の場合は1MBまで許可すること
     */
    public function testCheckMinFileSize(){
        $data = array(
            'file' => array('file_size' => 1 * 1024 * 1024),
        );
        $result = $this->FilebinderPost->checkMinFileSize($data, '1MB');
        $this->assertTrue($result);
    }

    /**
     * _setTestFile
     *
     * @return
     */
    private function _setTestFile($to = null){
        if (!$to) {
            return false;
        }
        $from = dirname(__FILE__) . '/../../../../Test/File/test.png';
        return copy($from, $to);
    }
}
