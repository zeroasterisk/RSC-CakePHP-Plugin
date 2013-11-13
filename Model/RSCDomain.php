<?php
App::uses('RSCAppModel','RSC.Model');
class RSCDomain extends RSCAppModel {
	public $name = 'RSCDomain';
	public $useTable = false;
	protected $_schema = array(
		'name' => array('type' => 'string', 'null' => false, 'length' => 255, 'primary' => true),
		'ttl' => array('type' => 'integer', 'length' => 11),
		'emailAddress' => array('type' => 'string', 'null' => false, 'length' => 255)
	);
	public $validate = array(
		'name' => array(
			'rule' => 'notempty',
			'allowEmpty' => false
		),
		'ttl' => array(
			'rule' => 'notempty',
		),
		'emailAddress' => array(
			'rule' => 'email',
			'allowEmpty' => true
		),
	);
	
	/**
	* Placeholder for DNS
	*/
	public $DNS = null;
	
	/**
	* 
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
		$this->RSCDomain->find('all', array(
			'conditions' => array(
				'name' => 'example.com'
			),
			'records' => true, //optional
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
		$retval = array();
		$dlist = $this->DNS->DomainList($filter);
		while ($domain = $dlist->Next()) {
			$rsc_domain = array();
			if (!empty($options['records'])) {
				$rlist = $domain->RecordList();
				while ($record = $rlist->Next()) {
					$rsc_domain['RSCRecord'][] = array(
						'name' => $record->Name(),
						'id' => $record->id,
						'type' => $record->type,
						'data' => $record->data,
						'ttl' => $record->ttl,
						'created' => $record->created,
						'updated' => $record->updated
					);
				}
			}
			$rsc_domain[$this->alias] = array(
				'name' => $domain->Name(),
				'id' => $domain->id,
				'ttl' => $domain->ttl,
				'created' => $domain->created,
				'emailAddress' => $domain->emailAddress
			);
			$retval[] = $rsc_domain;
		}
		if ($type === 'first' && !empty($retval)) {
			return $retval[0];
		}
		return $retval;
	}
	
	/**
	* Checks to see if a name exists
	* @param string name
	* @return boolean if exists
	*/
	public function exists($name = null) {
		if (!$this->DNS) {
			$this->_erorr('Unable to connect to DNS.');
		}
		if (empty($name)) {
			return false;
		}
		return !!$this->find('first', array(
			'conditions' => array('name' => $name)
		));
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
		if ($this->exists($data['name'])) {
			$Domain = $this->__getDomainObjectByName($data['name']);
			$Domain->update($data);
		} else {
			$Domain = $this->DNS->domain()->Create($data);
		}
		if ($Domain) {
			return array(
				$this->alias => array(
					'id' => $Domain->id,
					'name' => $Domain->Name(),
					'ttl' => $Domain->ttl,
					'created' => $Domain->created,
					'emailAddress' => $Domain->emailAddress
				)
			);
		}
		return false;
	}
	
	/**
	* Delete the Domain
	* @param string name
	* @param boolean cascade (ignored)
	*/
	public function delete($name = null, $cascade = true) {
		if (!$this->DNS) {
			$this->_erorr('Unable to connect to DNS.');
		}
		if (!$this->exists($name)) {
			$this->_erorr("$name doesn't exist on DNS.");
		}
		$Domain = $this->__getDomainObjectByName($name);
		$result = $Domain->delete();
		if (!empty($result)) {
			return true;
		}
		return false;
	}
	
	/**
	* Gives me the Domain object return because it's useful.
	* @param string name
	* @return RackSpace\Domain object
	*/
	private function __getDomainObjectByName($name = null) {
		if (!$this->DNS) {
			$this->_erorr('Unable to connect to DNS.');
		}
		if (!$this->exists($name)) {
			$this->_erorr("$name doesn't exist on DNS.");
		}
		$DomainList = $this->DNS->DomainList(array('name' => $name));
		return $DomainList->Next();
	}
}
