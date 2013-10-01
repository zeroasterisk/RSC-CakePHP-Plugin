<?php
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

}
