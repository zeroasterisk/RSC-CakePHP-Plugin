<?php
/**
 * Rackspace Cloud Plugin for CakePHP
 *
 * @license MIT
 * @author Alan Blount <alan@zeroasterisk.com>
 * @link https://github.com/zeroasterisk/RSC-CakePHP-Plugin
 */
App::uses('RSCDomain', 'RSC.Model');

/**
 * RSCTestCase
 *
 * @package       datasources
 * @subpackage    datasources.tests.cases.models.datasources
 */
class RSCDomainCase extends CakeTestCase {

	/**
	 * Start Test
	 *
	 * @return void
	 * @access public
	 */
	public function startTest($method){
		$this->RSCDomain = ClassRegistry::init('RSC.RSCDomain');
		$this->RSCDomain->useDbConfig = 'rsc';
		return parent::startTest($method);
	}

	/**
	 * End Test
	 *
	 * @return void
	 * @access public
	 */
	public function endTest($method){
		unset($this->RSCFile);
		return parent::endTest($method);
	}
	
	public function test_find() {
		$result = $this->RSCDomain->find('all', array(
			'conditions' => array(
				'name' => 'manage-website.com'
			),
			'records' => true,
		));
		$this->assertTrue(!empty($result[0]['RSCRecord']));
		$this->assertEqual(1, count($result));
		$this->assertEqual('manage-website.com',$result[0]['RSCDomain']['name']);
	}

}
