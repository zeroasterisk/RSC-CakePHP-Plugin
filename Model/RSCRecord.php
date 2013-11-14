<?php
App::uses('RSCAppModel','RSC.Model');
class RSCRecord extends RSCAppModel {
	public $name = 'RSCRecord';
	public $useTable = false;
	protected $_schema = array(
		'id' => array('type' => 'string', 'length' => '25', 'comment' => 'RSC identifier'),
		'zone' => array('type' => 'string', 'length' => '255', 'comment' => 'required to associate record with zone (aka domain name)'),
		'name' => array('type' => 'string', 'length' => '50', 'comment' => 'subdomain or record name'),
		'type' => array('type' => 'string', 'length' => '15', 'comment' => 'A, CNAME, MX, TXT, etc'),
		'data' => array('type' => 'string', 'length' => '100', 'comment' => 'The records value'),
		'ttl' => array('type' => 'integer', 'length' => '8', 'comment' => 'Time To Live'),
		'priority' => array('type' => 'integer', 'length' => '8', 'comment' => 'Priority field for MX records'),
	);
	public $validate = array(
		'name' => array(
			'rule' => 'notempty',
			'allowEmpty' => false
		),
		'type' => array(
			'rule' => 'notempty',
			'allowEmpty' => false,
		),
		'zone' => array(
			'rule' => 'notempty',
			'allowEmpty' => false,
		),
		'data' => array(
			'rule' => 'notempty',
			'allowEmpty' => false,
		),
		'priority' => array(
			'rule' => 'notempty',
		),
		'ttl' => array(
			'rule' => 'notempty',
		),
	);
	
	/**
	* Placeholder for DNS
	*/
	public $DNS = null;
	
	/**
	* Build the DNS object for me from RackSpace object.
	*/
	public function __construct($id = false, $table = null, $ds = null) {
		parent::__construct($id, $table, $ds);
		if ($this->connect()) {
			$this->DNS = $this->RackSpace->DNS();
		}
	}
	
	/**
	* Find function
	* @param string type
	* @param array options
	* @return array result
	* 
	* Examples
		$this->RSCRecord->find('all', array(
			'conditions' => array(
				'zone' => 'example.com'
			),
		));
	*/
	public function find($type = 'first', $options = array()){
		if (!$this->DNS) {
			$this->_erorr('Unable to connect to DNS.');
		}
		$filter = null;
		if (!empty($options['conditions'])) {
			$filter = $this->_stripOutModelAliasInConditions($options['conditions']);
		}
		if (empty($filter['zone'])) {
			$this->_error('Zone field not present. (aka example.com)');
		}
		$Domain = $this->__getDomainObjectByZone($filter['zone']);
		unset($filter['zone']);
		
		$retval = array();
		$records = $Domain->RecordList($filter);
		while ($record = $records->Next()) {
			$retval[] = array(
				$this->alias => array(
					'name' => $record->Name(),
					'id' => $record->id,
					'type' => $record->type,
					'data' => $record->data,
					'ttl' => $record->ttl,
					'created' => $record->created,
					'updated' => $record->updated,
					'zone' => $Domain->Name()
				)
			);
		}
		if ($type === 'first' && !empty($retval)) {
			return $retval[0];
		}
		return $retval;
	}
	
	/**
	* Checks to see if a name recordExists
	* @param string name
	* @return mixed boolean false if doesn't exist, returns array of Record if recordExists
	*/
	public function recordExists($name = null, $zone) {
		if (!$this->DNS) {
			$this->_erorr('Unable to connect to DNS.');
		}
		if (empty($name) || empty($zone)) {
			$this->_erorr('Zone and name required for exist search');
		}
		/** This doesn't work because conditions don't work.  Must traverse the whole record list for now.
		return !!$this->find('first', array(
			'conditions' => array('zone' => $zone, 'name' => $name)
		));*/
		
		$records = $this->find('all', array('conditions' => array('zone' => $zone)));
		foreach ($records as $record) {
			if ($record[$this->alias]['name'] === $name) {
				return $record;
			}
		}
		return false;
	}
	
	/**
	* Find record ID by name and zone
	* @param string name
	* @param string zone
	* @return int id or false if not found.
	*/
	public function findIdByNameAndZone($name, $zone) {
		if (!$this->DNS) {
			$this->_erorr('Unable to connect to DNS.');
		}
		if (empty($name) || empty($zone)) {
			$this->_erorr('Zone and name required for exist search');
		}
		if ($record = $this->recordExists($name, $zone)) {
			return $record[$this->alias]['id'];
		}
		return false;
	}
	
	/**
	* Saves and updates a domain
	* @param array of data
	* @param boolean validation
	* @param fieldList (ignored)
	* @return mixed array of domain or false if failure
	*/
	public function save($data = null, $validate = true, $fieldList = array()) {
		if (!$this->DNS) {
			$this->_erorr('Unable to connect to DNS.');
		}
		$this->set($data);
		if ($validate && !$this->validates()) {
			return false;
		}
		if (isset($data[$this->alias])) {
			$data = $data[$this->alias];
		}
		$zone = $data['zone'];
		unset($data['zone']);
		if ($this->recordExists($data['name'], $zone)) {
			$Record = $this->__getRecordByNameAndZone($data['name'], $zone);
			$Record->update($data);
		} else {
			$Domain = $this->__getDomainObjectByZone($zone);
			$Record = $Domain->Record()->Create($data);
		}
		if ($Record) {
			return array(
				$this->alias => array(
					'id' => $Record->Id(),
					'name' => $Record->Name(),
					'zone' => $zone,
					'type' => $Record->type,
					'ttl' => $Record->ttl,
					'data' => $Record->data,
					'created' => $Record->created,
					'updated' => $Record->updated,
				)
			);
		}
		return false;
	}
	
	/**
	* Delete the Domain
	* @param string name
	* @param string zone
	* @return boolean success
	*/
	public function delete($name = null, $zone = true /* true is for strict compliance */) {
		if (!$this->DNS) {
			$this->_erorr('Unable to connect to DNS.');
		}
		if (empty($name) || $zone === true || empty($zone)) {
			$this->_error('zone and name are required to delete a record');
		}
		if (!$this->recordExists($name, $zone)) {
			$this->_erorr("$name doesn't exist on $zone DNS.");
		}
		$Record = $this->__getRecordByNameAndZone($name, $zone);
		$result = $Record->delete();
		if (!empty($result)) {
			return true;
		}
		return false;
	}
	
	/**
	* Gives me the Domain object return because it's useful.
	* @param string zone
	* @return RackSpace\Domain object
	*/
	private function __getDomainObjectByZone($zone = null) {
		if (!$this->DNS) {
			$this->_erorr('Unable to connect to DNS.');
		}
		$DomainList = $this->DNS->DomainList(array('name' => $zone));
		if ($DomainList->Size() == 0) {
			$this->_error("$zone does not exist.");
		}
		return $DomainList->Next();
	}
	
	/**
	* Gives me the Record object return becasue it's useful
	* @param string name
	* @Param string zone
	* @return RackSpace\Domain\Record object
	*/
	private function __getRecordByNameAndZone($name, $zone) {
		if (!$this->DNS) {
			$this->_erorr('Unable to connect to DNS.');
		}
		$id = $this->findIdByNameAndZone($name, $zone);
		if (!$id) {
			$this->_error("$name not found in $zone");
		}
		$Domain = $this->__getDomainObjectByZone($zone);
		return $Domain->Record($id);
	}
}
