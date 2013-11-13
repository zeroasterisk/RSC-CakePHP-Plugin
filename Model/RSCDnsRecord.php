<?php
App::uses('RSCAppModel','RSC.Model');
class RSCDnsRecord extends RSCAppModel {
	public $name = 'RSCDnsRecord';
	public $useTable = false;
	
	protected $_schema = array(
		'id' => array('type' => 'string', 'length' => '25', 'comment' => 'RSC identifier'),
		'domainId' => array('type' => 'integer', 'length' => '11', 'comment' => 'required to associate record with correct zone'),
		'name' => array('type' => 'string', 'length' => '50', 'comment' => 'subdomain or record name'),
		'type' => array('type' => 'string', 'length' => '15', 'comment' => 'A, CNAME, MX, TXT, etc'),
		'data' => array('type' => 'string', 'length' => '100', 'comment' => 'The records value'),
		'ttl' => array('type' => 'integer', 'length' => '8', 'comment' => 'Time To Live'),
		'priority' => array('type' => 'integer', 'length' => '8', 'comment' => 'Priority field for MX records'),
	);
	
	
	
	//Get Record
	function getRecord ($zone, $name, $type='A') {
		$records = $this->getRecords($zone);
		foreach ($records['records'] as $record) {
			if ($record['name'] == $name && $record['type'] == $type) {
				return $record;
			}
		}
		return array();
	}
	
	//Get Records For Domain
	function getRecords($zone) {
		$domainId = $this->getDomainId($zone);
		$conditions = array('domainId'=>$domainId);
		$records = $this->find('all',array('conditions'=>$conditions));
		return $records;
	}
	
	//Add Single Record
	/*
	*	@param $zone - example.com - Zone we're adding a record to
	*	@param $name - test.example.com - Full record name we're adding
	* 	@param $type - CNAME - Type of DNS record
	*	@param $data - dev.example.com - Data for this record
	* 	
	*  Example: addRecord('example.com', 'test.example.com', 'CNAME', 'dev.example.com')
	*/
	function addRecord($zone, $name, $type, $data, $ttl=3600, $priority=null) {
		$domainId = $this->getDomainId($zone);
		$recordData = array(
			'id'=>null,
			'domainId' => $domainId,
			'name'=> $name,
			'type'=>$type,
			'data' => $data,
			'ttl' => $ttl
			);
		if (!is_null($priority) && $type == 'MX') {
			$recordData['priority'] = $priority;
		}
		$result = $this->save($recordData);
		return $result;
	}
	
	//Update single record
	function updateRecord($zone, $name, $type, $data, $ttl=3600, $priority=null) {
		$recordId = $this->getRecordId($zone, $name, $type);
		$domainId = $this->getDomainId($zone);
		$recordData = array(
			'id'=>$recordId,
			'domainId' => $domainId,
			'name'=> $name,
			'type'=>$type,
			'data' => $data,
			'ttl' => $ttl
			); 
		if (!is_null($priority) && $type == 'MX') {
			$recordData['priority'] = $priority;
		}
		return $this->save($recordData);		
	}
	
	//Delete single record
	function deleteRecord($zone, $name, $type) {
		$recordId = $this->getRecordId($zone, $name, $type);
		return $this->delete($recordId);
	}
	
	//Get RecordId
	function getRecordId($zone, $name, $type) {
		$record = $this->getRecord($zone, $name, $type);
		return (isset($record['id']) ? $record['id'] : '');
	}
	
	
	//Get DomainId
	function getDomainId($zone, $refresh=false) {
		$id = RscdnsUtil::getConfig('domainId');
		if (empty($id) || $refresh) {
			$id = ClassRegistry::init('Rscdns.RscdnsDomain')->getDomainId($zone);
			RscdnsUtil::setConfig('domainId',$id);
			return $id;
		} else {
			return $id;
		}
		
	}
	
		
}

?>
