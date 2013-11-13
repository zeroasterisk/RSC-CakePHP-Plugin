<?php
class RSCException extends CakeException {}
/**
 * Rackspace Cloud Plugin for CakePHP
 *
 * @license MIT
 * @author Alan Blount <alan@zeroasterisk.com>
 * @link https://github.com/zeroasterisk/RSC-CakePHP-Plugin
 */
App::uses('AppModel', 'Model');
class RSCAppModel extends AppModel {

	/**
	 * should be configured in app/Config/database.php
	 */
	public $useDbConfig = 'rsc';
	
	/**
	* Placeholder
	*/
	public $RackSpace = null;

	/**
	 *
	 */
	public $useTable = false;

	/**
	 * Quick and dirty function to return the datasource object
	 *
	 * @return object $datasource
	 */
	public function datasource() {
		// NOTE: useDbConfig is changed to 'test' during testing...
		// so setting this explicitly
		return ConnectionManager::getDataSource('rsc');
	}
	
	/**
	* Connect to rackspace if we haven't already
	* @return boolean success if connected via RackSpace
	*/
	public function connect() {
		if (!$this->RackSpace) {
			$this->RackSpace = $this->datasource()->connect();
		}
		return !!$this->RackSpace;
	}

	protected function _error($message) {
		throw new RSCException($message);
	}
	
	/**
	* Strip out the model alias out of the conditions array.
	*/
	protected function _stripOutModelAliasInConditions($conditions = array()) {
		if (empty($conditions) || empty($this->alias)) {
			return $conditions;
		}
		$retval = array();
		foreach ($conditions as $key => $value) {
			$new_key = str_replace($this->alias . '.', '', $key);
			$retval[$new_key] = $value;
		}
		return $retval;
	}
}
