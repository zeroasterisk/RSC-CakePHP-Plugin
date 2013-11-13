<?php
/**
 * Rackspace Cloud Plugin for CakePHP
 *
 * @license MIT
 * @author Alan Blount <alan@zeroasterisk.com>
 * @link https://github.com/zeroasterisk/RSC-CakePHP-Plugin
 */
App::uses('RSCDnsRecord', 'RSC.Model');

/**
 * RSCTestCase
 *
 * @package       datasources
 * @subpackage    datasources.tests.cases.models.datasources
 */
class RSCDnsRecordCase extends CakeTestCase {

	/**
	 * Start Test
	 *
	 * @return void
	 * @access public
	 */
	public function startTest($method){
		$this->RSCDnsRecord = ClassRegistry::init('RSC.RSCDnsRecord');
		$this->RSCDnsRecord->useDbConfig = 'rsc';
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
		$result = $this->RSCDnsRecord->find('all', array(
			'conditions' => array(
				'name' => 'manage-website.com'
			)
		));
	}

}
