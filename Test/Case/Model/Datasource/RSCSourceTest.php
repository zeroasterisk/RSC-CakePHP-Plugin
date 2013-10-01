<?php
/**
 * Rackspace Cloud Plugin for CakePHP
 *
 * @license MIT
 * @author Alan Blount <alan@zeroasterisk.com>
 * @link https://github.com/zeroasterisk/RSC-CakePHP-Plugin
 */
App::uses('RSCSource', 'RSC.Model/Datasource');

/**
 * RSCTestCase
 *
 * @package       datasources
 * @subpackage    datasources.tests.cases.models.datasources
 */
class RSCTestCase extends CakeTestCase {

	/**
	 * Datasource object
	 *
	 * @var MockRSCAssocatesSource
	 * @access public
	 */
	var $RSC = null;

	/**
	 * Configuration
	 *
	 * @var array
	 * @access public
	 */
	var $config = array(
		'key' => 'PUBLICKEY',
		'secret' => 'SECRETKEY',
		'tag' => 'ASSID',
		'locale' => 'com' //(ca,com,co,uk,de,fr,jp)
	);

	/**
	 * Start Test
	 *
	 * @return void
	 * @access public
	 */
	public function startTest($method){
		$this->RSC = $this->getMock('RSCSource', array('_request'), array($this->config));
	}

	/**
	 * End Test
	 *
	 * @return void
	 * @access public
	 */
	public function endTest($method){
		unset($this->RSC);
	}

	/**
	 * test setConfig
	 *
	 * @return void
	 * @access public
	 */
	public function testSetConfig() {
		$expect = $this->RSC->config;
		$input = array('a' => 'A', 'b' => array(1,2,3));
		$this->RSC->setConfig($input);
		if (!Configure::check('RSC')) {
			try {
				Configure::load('rsc');
			} catch (ConfigureException $e) {
				// no configure file... no problem
			}
		}
		$configFromConfigure = Configure::read('RSC');
		if (is_array($configFromConfigure) && !empty($configFromConfigure)) {
			$expect = array_merge($configFromConfigure, $expect);
		}
		$expect = array_merge($expect, $input);
		$this->assertEqual($expect, $this->RSC->config);
		// -------------------------
		// explicit injection
		Configure::write('RSC', array(
			'a' => 'Configged',
			'c' => $input,
		));
		$this->RSC->setConfig($input);
		$configFromConfigure = Configure::read('RSC');
		$expect = array_merge($configFromConfigure, $expect);
		$expect = array_merge($expect, $input);
		$expect = array_merge($expect, $input);
		$this->assertEqual($expect, $this->RSC->config);
	}

	public function testConnect() {
		$result = $this->RSC->connect();
		$this->assertTrue(is_object($result));
	}

	public function testConnectFaked() {
		$this->RSC->connected = true;
		$result = $this->RSC->connect();
		$this->assertTrue(is_null($result));
	}
}
