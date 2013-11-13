<?php
/**
 * Rackspace Cloud Plugin for CakePHP
 *
 * @license MIT
 * @author Alan Blount <alan@zeroasterisk.com>
 * @link https://github.com/zeroasterisk/RSC-CakePHP-Plugin
 */
App::uses('RSCFile', 'RSC.Model');

/**
 * RSCTestCase
 *
 * @package       datasources
 * @subpackage    datasources.tests.cases.models.datasources
 */
class RSCFileCase extends CakeTestCase {

	/**
	 * Datasource object
	 *
	 * @var MockRSCAssocatesSource
	 * @access public
	 */
	public $RSC = null;
	public $config = array();
	public $containerName = 'unittest-RSC.RSCFile';
	// set in startTest
	public $Config_path = null;
	public $testfile_js = null;
	public $testfile_js_hash = null;
	public $testfile_css = null;
	public $testfile_css_hash = null;
	public $testfile_img = null;
	public $testfile_img_content = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVQIW2P4zwAAAgEBAFb7bLkAAAAASUVORK5CYII=';


	/**
	 * We need some test files to work with...
	 */
	public function setupTestFiles() {
		$css = '
		.example-urla {
			color: black;
			background: transparent url("/img/rscfiletest.png") no-repeat center center;
		}
		.example-background-image {
			background-image: url("/img/rscfiletest.png");
		}
		.example-background-a {
			background-image: url(\'/img/rscfiletest-a.png\');
		}
		.example-background-b {
			background-image: url("/img/rscfiletest-b.png");
		}
		.example-background-c {
			background-image: url(/img/rscfiletest-c.png);
		}
		.example-background-relative {
			background-image: url("../img/rscfiletest-relative.png");
		}
		.example-background-missing {
			background-image: url("/img/rscfiletest-missing-file.png");
		}
		';
		$this->testfile_css = WWW_ROOT . 'css' . DS . 'rscfiletest.css';
		file_put_contents($this->testfile_css, $css);
		$this->testfile_css_hash = md5(file_get_contents($this->testfile_css));

		$js = '
		blah = "blah blah";
		var stuff = function() { alert(\'yo\'); };
		junk = ' . rand() . time() . ';
		';
		$this->testfile_js = WWW_ROOT . 'js' . DS . 'rscfiletest.js';
		file_put_contents($this->testfile_js, $js);
		$this->testfile_js_hash = md5(file_get_contents($this->testfile_js));

		$this->testfile_img = WWW_ROOT . 'img' . DS . 'rscfiletest.png';
		$testfile_img_content = base64_decode($this->testfile_img_content);
		file_put_contents($this->testfile_img, $testfile_img_content);
		file_put_contents(str_replace('.png', '-a.png', $this->testfile_img), $testfile_img_content);
		file_put_contents(str_replace('.png', '-b.png', $this->testfile_img), $testfile_img_content);
		file_put_contents(str_replace('.png', '-c.png', $this->testfile_img), $testfile_img_content);
		file_put_contents(str_replace('.png', '-relative.png', $this->testfile_img), $testfile_img_content);
	}
	public function killTestFiles() {
		@unlink($this->testfile_js);
		@unlink($this->testfile_css);
		@unlink($this->testfile_img);
		@unlink(str_replace('.png', '-a.png', $this->testfile_img));
		@unlink(str_replace('.png', '-b.png', $this->testfile_img));
		@unlink(str_replace('.png', '-c.png', $this->testfile_img));
		@unlink(str_replace('.png', '-relative.png', $this->testfile_img));
		$this->RSCFile->delete(basename($this->testfile_img));
		$this->RSCFile->delete(basename($this->testfile_css));
		$this->RSCFile->delete(basename($this->testfile_js));
	}

	/**
	 * Start Test
	 *
	 * @return void
	 * @access public
	 */
	public function startTest($method){
		//$this->RSCFile = $this->getMockForModel('RSC.RSCFile', array(), array($this->config));
		$this->RSCFile = ClassRegistry::init('RSC.RSCFile');
		$this->RSCFile->useDbConfig = 'rsc';
		$this->setupTestFiles();
		return parent::startTest($method);
	}

	/**
	 * End Test
	 *
	 * @return void
	 * @access public
	 */
	public function endTest($method){
		$this->killTestFiles();
		unset($this->RSCFile);
		return parent::endTest($method);
	}

	/**
	 * test setConfig
	 *
	 * @return void
	 * @access public
	 */
	public function testContainer() {
		$container = $this->RSCFile->container($this->containerName);
		$this->assertTrue(is_object($container));
	}

	public function testUpload() {
		// img
		$this->assertTrue(is_file($this->testfile_img));
		$fileobj = $this->RSCFile->upload($this->testfile_img, $this->containerName);
		$this->assertTrue(is_object($fileobj));
		$url = $fileobj->PublicURL();
		$this->assertPattern('#https?://.*\.png#', $url);
		// css
		$this->assertTrue(is_file($this->testfile_css));
		$fileobj = $this->RSCFile->upload($this->testfile_css, $this->containerName);
		$this->assertTrue(is_object($fileobj));
		$url = $fileobj->PublicURL();
		$this->assertPattern('#https?://.*\.css#', $url);
	}

	public function testDelete() {
		// setup
		$fileobj = $this->RSCFile->upload($this->testfile_img, $this->containerName);
		$url = $fileobj->PublicURL();
		$this->assertPattern('#https?://.*\.png#', $url);
		// test
		$this->assertTrue($this->RSCFile->delete(basename($this->testfile_img)));
		// setup
		$fileobj = $this->RSCFile->upload($this->testfile_img, $this->containerName);
		$url = $fileobj->PublicURL();
		$this->assertPattern('#https?://.*\.png#', $url);
		// test
		$this->RSCFile->id = basename($this->testfile_img);
		$this->assertTrue($this->RSCFile->exists(basename($this->testfile_img)));
		$this->assertTrue($this->RSCFile->delete());
		$this->assertFalse($this->RSCFile->exists(basename($this->testfile_img)));
	}

	public function testExists() {
		// setup
		$fileobj = $this->RSCFile->upload($this->testfile_img, $this->containerName);
		$url = $fileobj->PublicURL();
		$this->assertPattern('#https?://.*\.png#', $url);
		// test
		$this->assertTrue($this->RSCFile->exists(basename($this->testfile_img)));
		$this->assertFalse($this->RSCFile->exists('something-crazy'));
		$this->RSCFile->id = null;
		$this->assertFalse($this->RSCFile->exists());
		$this->RSCFile->id = $this->testfile_img;
		$this->assertFalse($this->RSCFile->exists());
		$this->RSCFile->id = basename($this->testfile_img);
		$this->assertTrue($this->RSCFile->exists());
	}

	public function testRead() {
		// setup
		$fileobj = $this->RSCFile->upload($this->testfile_img, $this->containerName);
		$url = $fileobj->PublicURL();
		$this->assertPattern('#https?://.*\.png#', $url);
		// test
		$obj = $this->RSCFile->read(null, basename($this->testfile_img));
		$this->assertTrue(is_object($obj));
		$url = $obj->PublicURL();
		$this->assertPattern('#https?://.*\.png#', $url);
	}
}

