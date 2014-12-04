# Filebinder: Simple file attachment plugin for CakePHP

![Image](https://raw.github.com/fusic/filebinder/2.0/Document/filebinder.png)

[![Build Status](https://travis-ci.org/fusic/filebinder.png?branch=2.0)](https://travis-ci.org/fusic/filebinder)

## Feature

- Simple settings
- Compatible with Transition Component
- Multi attachement
- Selectalble file store method (DB storage or not)

## Requirements

- PHP >= 5.2.6
- CakePHP >= 2.0

## Installation

Put 'Filebinder' directory on app/plugins in your CakePHP application.
Then, add the following code in bootstrap.php

    <?php
        CakePlugin::load('Filebinder');

## Filebinder outline image

Filebinder manage 'virtual table' and entity.

### 'Simple attachment' model image

![Image](https://raw.github.com/fusic/filebinder/2.0/Document/filebinder_image.png)

### 'Multi fields' model image

![Image](https://raw.github.com/fusic/filebinder/2.0/Document/filebinder_multi_fields.png)

### 'Multi models' model image

![Image](https://raw.github.com/fusic/filebinder/2.0/Document/filebinder_multi_models.png)

### Entity file path image

![Image](https://raw.github.com/fusic/filebinder/2.0/Document/filebinder_filepath.png)

## Usage

Example of how to add image file with confirm page.

    <?php
    class Post extends AppModel {
       public $name = 'Post';
       public $actsAs = array('Filebinder.Bindable');
       public $displayField = 'title';
          
       public $bindFields = array(array('field' => 'image',
                                     'tmpPath' => '/var/www/html/myapp/app/webroot/files/cache/',
                                     'filePath' => '/var/www/html/myapp/app/webroot/files/',
                                     ));
          
       public $validate = array('title' => array('notempty'),
                             'image' => array('allowExtention' => array('rule' => array('checkExtension', array('jpg')),
                                                                       'allowEmpty' => true),
                                              'illegalCode' => array('rule' => array('funcCheckFile', 'checkIllegalCode'),
                                                                    'allowEmpty' => true))
                             );
    
       /**
        * checkIllegalCode
        * check include illegal code
        *
        * @param $filePath
        * @return
        */
       public function checkIllegalCode($filePath){
           $fp = fopen($filePath, "r");
           $ofile = fread($fp, filesize($filePath));
           fclose($fp);
    
           if (preg_match('/<\\?php./i', $ofile)) {
               return false;
           }
           return true;
       }
     }

Create attachment table.

    CREATE TABLE `attachments` (
     `id` int(11) NOT NULL AUTO_INCREMENT,
     `model` text NOT NULL,
     `model_id` int(11) NOT NULL,
     `field_name` text NOT NULL,
     `file_name` text NOT NULL,
     `file_content_type` text NOT NULL,
     `file_size` int(11) NOT NULL,
     `file_object` longtext,
     `created` datetime DEFAULT NULL,
     `modified` datetime DEFAULT NULL,
     PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;

    <?php
    class PostsController extends AppController {
     
        public $name = 'Posts';
        public $components = array('Session', 'Filebinder.Ring', 'Transition');
     
        /**
         * add
         */
        public function add() {
            $this->Ring->bindUp();
            $this->Transition->checkData('add_confirm');
            $this->Transition->clearData();
        }
     
        /**
         * add_confirm
         */
        public function add_confirm(){
            $this->Transition->checkPrev(array('add'));
     
            $this->Transition->automate('add_success',
                                        false,
                                        'add');
            $mergedData = $this->Transition->mergedData();
            $this->set('mergedData', $mergedData);
        }
     
        /**
         * add_success
         */
        public function add_success(){
            $this->Transition->checkPrev(array('add',
                                               'add_confirm'));
            $mergedData = $this->Transition->mergedData();
     
            if ($this->Post->save($mergedData)) {
                $this->Transition->clearData();
                $this->Session->setFlash(sprintf(__('The %s has been saved', true), 'post'));
                $this->redirect(array('action' => 'index'));
            } else {
                $this->Session->setFlash(sprintf(__('The %s could not be saved. Please, try again.', true), 'post'));
                $this->redirect(array('action' => 'add'));
            }
        }
    }

 add.ctp

    <div class="posts form">
      <h2><?php printf(__('Add %s', true), __('Post', true)); ?></h2>
      <?php echo $this->Form->create('Post', array('action' => 'add', 'type' => 'file'));?>
      <?php echo $this->Form->input('title', array('type' => 'text'));?>
      <?php echo $this->Form->input('body');?>
      <?php echo $this->Form->input('image', array('type' => 'file'));?>
      <?php echo $this->Form->submit(__('Submit', true));?>
      <?php echo $this->Form->end();?>
    </div>

 add_confirm.ctp

    <div class="posts form">
      <h2><?php printf(__('Confirm %s', true), __('Post', true)); ?></h2>
      
      <?php echo h($mergedData['Post']['title']);?>
      <?php echo h($mergedData['Post']['body']);?>
      <?php echo $this->Label->image($mergedData['Post']['image']);?> 
      <?php echo $this->Form->create('Post', array('action' => 'add_confirm'));?>   
      <?php echo $this->Form->input('dummy', array('type' => 'hidden'));?>
      <?php echo $this->Form->submit(__('Submit', true));?>
      <?php echo $this->Form->end();?>
    </div>


## Amazon S3 combination

### Requirements

- aws-sdk 1.5.*

### Setting


    <?php
    Configure::write('Filebinder.S3.key', '************************');
    Configure::write('Filebinder.S3.secret', '********************************************');
    Configure::write('Filebinder.S3.region', AmazonS3::REGION_TOKYO);


    <?php
    class Post extends AppModel {
        public $name = 'Post';
        public $actsAs = array('Filebinder.Bindable' => array('strage' => array('Db', 'S3'))); // using Database and Amazon S3 for object storage
        public $displayField = 'title';
           
        public $bindFields = array(array(
                                  'field' => 'image',
                                  'tmpPath' => '/var/www/html/myapp/app/webroot/files/cache/',
                                  'filePath' => '/var/www/html/myapp/app/webroot/files/',
                                  'bucket' => 'aws.foobacket', // bucket name,
                                  'acl' => AmazonS3::ACL_PUBLIC, // S3 ACL
                                  ));
    }


## License

The MIT License

Copyright (c) 2010-2011 Fusic Co., Ltd. (http://fusic.co.jp)

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
