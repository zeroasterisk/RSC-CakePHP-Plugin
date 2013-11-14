<?php
/**
 * Rackspace Cloud Plugin for CakePHP
 *
 * @license MIT
 * @author Nick Baker
 * @link https://github.com/zeroasterisk/RSC-CakePHP-Plugin
 */
App::uses('RSCRecord', 'RSC.Model');
class RSCRecordCase extends CakeTestCase {

	/**
	 * Start Test
	 *
	 * @return void
	 * @access public
	 */
	public function startTest($method){
		$this->RSCRecord = ClassRegistry::init('RSC.RSCRecord');
		$this->RSCRecord->useDbConfig = 'rcs';
		return parent::startTest($method);
	}

	/**
	 * End Test
	 *
	 * @return void
	 * @access public
	 */
	public function endTest($method){
		unset($this->RSCRecord);
		return parent::endTest($method);
	}
	
	public function test_find() {
		$result = $this->RSCRecord->find('all', array(
			'conditions' => array(
				'zone' => 'manage-website.com'
			),
		));
		$this->assertTrue(!empty($result));
		$this->assertEqual('manage-website.com', $result[0]['RSCRecord']['zone']);
		
		//Should work according to API I'm sending the right data through but still gets all records.
		/*$result = $this->RSCRecord->find('all', array(
			'conditions' => array(
				'zone' => 'manage-website.com',
				'type' => 'A'
			)
		));*/
	}

	public function test_save_validates() {
		$data = array(
			'name' => '',
			'zone' => '',
		);
		$result = $this->RSCRecord->save($data);
		$this->assertFalse($result);
		$this->assertTrue(!empty($this->RSCRecord->validationErrors['name']));
		$this->assertTrue(!empty($this->RSCRecord->validationErrors['zone']));
	}
	
	public function test_save() {
		//Create a Domain to play with.
		$this->__buildTestDomain();
		
		$record_data = array(
			'zone' => 'nick-is-awesome.com',
			'name' => 'pop.nick-is-awesome.com',
			'type' => 'CNAME',
			'data' => 'pop.gmail.com',
			'ttl' => '3600',
		);
		
		$result = $this->RSCRecord->save($record_data);
		$this->assertTrue(!empty($result));
		$this->assertEqual('pop.nick-is-awesome.com', $result['RSCRecord']['name']);
		$this->assertEqual('pop.gmail.com', $result['RSCRecord']['data']);
		$this->assertEqual('CNAME', $result['RSCRecord']['type']);
		
		//Test Update
		$record_data = array(
			'zone' => 'nick-is-awesome.com',
			'name' => 'pop.nick-is-awesome.com',
			'type' => 'CNAME',
			'data' => 'pop.yahoo.com',
			'ttl' => '3600',
		);
		
		$result = $this->RSCRecord->save($record_data);
		$this->assertTrue(!empty($result));
		$this->assertEqual('pop.nick-is-awesome.com', $result['RSCRecord']['name']);
		$this->assertEqual('pop.yahoo.com', $result['RSCRecord']['data']);
		$this->assertEqual('CNAME', $result['RSCRecord']['type']);

		//Cleanup
		$this->__deleteTestDomain();
	}
	
	public function test_delete() {
		//Create a Domain to play with.
		$this->__buildTestDomain();
		
		//Create a record to delete.
		$record_data = array(
			'zone' => 'nick-is-awesome.com',
			'name' => 'pop.nick-is-awesome.com',
			'type' => 'CNAME',
			'data' => 'pop.gmail.com',
			'ttl' => '3600',
		);
		$result = $this->RSCRecord->save($record_data);
		$this->assertTrue(!empty($result));
		$this->assertEqual('pop.nick-is-awesome.com', $result['RSCRecord']['name']);
		$this->assertEqual('pop.gmail.com', $result['RSCRecord']['data']);
		$this->assertEqual('CNAME', $result['RSCRecord']['type']);
		
		//Delete the record.
		$result = $this->RSCRecord->delete('pop.nick-is-awesome.com', 'nick-is-awesome.com');
		$this->assertTrue($result);
		
		//Assert it's actually gone.
		$result = $this->RSCRecord->recordExists('pop.nick-is-awesome.com', 'nick-is-awesome.com');
		$this->assertFalse($result);
		
		//Cleanup
		$this->__deleteTestDomain();
	}
	
	private function __buildTestDomain() {
		$RSCDomain = ClassRegistry::init('RSC.RSCDomain');
		if (!$RSCDomain->exists('nick-is-awesome.com')) {
			$data = array(
				'name' => 'nick-is-awesome.com',
				'emailAddress' => 'nbaker@healthyhearing.com',
				'ttl' => 3600
			);
			
			$result = $RSCDomain->save($data);
			$this->assertEqual('nick-is-awesome.com', $result['RSCRecord']['name']);
			$this->assertEqual('nbaker@healthyhearing.com', $result['RSCRecord']['emailAddress']);
		}
	}
	
	private function __deleteTestDomain() {
		$RSCDomain = ClassRegistry::init('RSC.RSCDomain');
		if ($RSCDomain->exists('nick-is-awesome.com')) {
			$result = $RSCDomain->delete('nick-is-awesome.com');
			$this->assertTrue($result);
		}
	}
}
