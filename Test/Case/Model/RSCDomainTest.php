<?php
/**
 * Rackspace Cloud Plugin for CakePHP
 *
 * @license MIT
 * @author Nick Baker
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

	public function test_save_validates() {
		$data = array(
			'name' => '',
		);
		$result = $this->RSCDomain->save($data);
		$this->assertFalse($result);
		$this->assertTrue(!empty($this->RSCDomain->validationErrors['name']));
	}
	
	public function test_save() {
		//Test create.
		$data = array(
			'name' => 'nick-is-awesome.com',
			'emailAddress' => 'nbaker@healthyhearing.com'
		);
		$result = $this->RSCDomain->save($data);
		$this->assertEqual('nick-is-awesome.com', $result['RSCDomain']['name']);
		$this->assertEqual('nbaker@healthyhearing.com', $result['RSCDomain']['emailAddress']);
		
		//Test update
		$data = array(
			'name' => 'nick-is-awesome.com',
			'emailAddress' => 'nick.baker@alliedhealthmedia.com'
		);
		$result = $this->RSCDomain->save($data);
		$this->assertEqual('nick-is-awesome.com', $result['RSCDomain']['name']);
		$this->assertEqual('nick.baker@alliedhealthmedia.com', $result['RSCDomain']['emailAddress']);
	}
	
	public function test_delete() {
		if ($this->RSCDomain->exists('nick-is-awesome.com')) {
			$result = $this->RSCDomain->delete('nick-is-awesome.com');
			$this->assertTrue($result);
		}
	}
}
