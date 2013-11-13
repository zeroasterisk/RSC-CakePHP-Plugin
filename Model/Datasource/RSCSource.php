<?php
class RSCSourceException extends CakeException {}
/**
 * Rackspace Cloud Plugin for CakePHP
 *
 * @license MIT
 * @author Alan Blount <alan@zeroasterisk.com>
 * @link https://github.com/zeroasterisk/RSC-CakePHP-Plugin
 */
App::uses('DataSource', 'Model/Datasource');
App::import('Vendor', 'RSC.php-opencloud/lib/php-opencloud');
class RSCSource extends DataSource {

	/**
	 * An optional description of your datasource
	 */
	public $description = 'Rackspace Cloud Datasource for CakePHP';

	/**
	 * Our default config options. These options will be customized in our
	 * ``app/Config/database.php`` and will be merged in the ``__construct()``.
	 */
	public $config = array(

	);

	protected $_schema = array();

	// placeholders
	public $connection = null;
	public $connected = false;

	/**
	 * Create our HttpSocket and handle any config tweaks.
	 */
	public function __construct($config) {
		$config = $this->assignConfig($config);
		parent::__construct($config);
	}

	/**
	 * Sets configuration
	 *
	 */
	public function assignConfig($config) {
		if (!Configure::check('RSC')) {
			try {
				Configure::load('rsc');
			} catch (ConfigureException $e) {
				// no configure file... no problem
			}
		}
		$configFromConfigure = Configure::read('RSC');
		if (is_array($configFromConfigure) && !empty($configFromConfigure)) {
			$config = array_merge($configFromConfigure, $config);
		}
		$this->config = array_merge( $this->config, $config );
		return $this->config;
	}

	/**
	 * Connects to the database. Options are specified in the $config instance variable
	 *
	 * @return boolean Connected
	 */
	public function connect() {
		if ($this->connected === true) {
			return $this->connection;
		}
		// initialize API
		$authurl = (!empty($this->config['country']) && $this->config['country'] == 'UK' ? RACKSPACE_UK : RACKSPACE_US);
		$auth = array(
			'username' => $this->config['username'],
			'apiKey' => $this->config['api_key'],
		);
		$this->connection = new \OpenCloud\Rackspace($authurl, $auth);
		// verify connection
		if (!is_object($this->connection) || get_class($this->connection) != 'OpenCloud\Rackspace') {
			throw new OutOfBoundsException('RSCSource::connect() unable to establish connection to Rackspace Cloud');
		}
		$this->connected = true;
		return $this->connection;
	}

	/**
	 * Since datasources normally connect to a database there are a few things
	 * we must change to get them to work without a database.
	 */

	/**
	 * listSources() is for caching. You'll likely want to implement caching in
	 * your own way with a custom datasource. So just ``return null``.
	 */
	public function listSources($data = null) {
		return null;
	}

	/**
	 * describe() tells the model your schema for ``Model::save()``.
	 *
	 * You may want a different schema for each model but still use a single
	 * datasource. If this is your case then set a ``schema`` property on your
	 * models and simply return ``$model->schema`` here instead.
	 */
	public function describe($model) {
		return $this->_schema;
	}

	/**
	 * calculate() is for determining how we will count the records and is
	 * required to get ``update()`` and ``delete()`` to work.
	 *
	 * We don't count the records here but return a string to be passed to
	 * ``read()`` which will do the actual counting. The easiest way is to just
	 * return the string 'COUNT' and check for it in ``read()`` where
	 * ``$data['fields'] === 'COUNT'``.
	 */
	public function calculate(Model $model, $func, $params = array()) {
		return 'COUNT';
	}

	/**
	 * Implement the R in CRUD. Calls to ``Model::find()`` arrive here.
	 */
	public function read(Model $model, $queryData = array(), $recursive = null) {
		$read_function = "__{$model->alias}_read";
		if (is_callable(array($this, $read_function))) {
			return call_user_func(array($this, $read_function), $model, $queryData, $recursive);
		}
		$this->__error("RSCSource::$read_function not defined.");
	}
	
	/**
	 * Implement the C in CRUD. Calls to ``Model::save()`` without $model->id
	 * set arrive here.
	 */
	public function create(Model $model, $fields = null, $values = null) {
		$data = array_combine($fields, $values);
		$create_function = "__{$model->alias}_create";
		if (is_callable(array($this, $create_function))) {
			return call_user_func(array($this, $create_function), $model, $fields, $values);
		}
		$this->__error("RSCSource::$create_function not defined.");
	}
	
	
	
	private function __RSCFile_read(Model $model, $queryData = array(), $recursive = null) {
		if (!isset($queryData['container'])) {
			$this->__error('container not defined in find condition.');
		}
		//TODO
	}
	
	private function __RSCDomain_read(Model $model, $queryData = array(), $recursive = null) {
		if (!$this->connected) {
			$this->connect();
		}
		$dns = $this->connection->DNS();
		$filter = null;
		if (!empty($queryData['conditions'])) {
			$filter = $queryData['conditions'];
		}
		$dlist = $dns->DomainList($filter);
		while ($domain = $dlist->Next()) {
			$rsc_domain = array();
			if (!empty($queryData['records'])) {
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
			$rsc_domain[$model->alias] = array(
				'name' => $domain->Name(),
				'id' => $domain->id,
				'ttl' => $domain->ttl,
				'created' => $domain->created,
			);
			$retval[] = $rsc_domain;
		}
		return $retval;
	}

	/**
	 * Implement the U in CRUD. Calls to ``Model::save()`` with $Model->id
	 * set arrive here. Depending on the remote source you can just call
	 * ``$this->create()``.
	 */
	public function update(Model $model, $fields = null, $values = null, $conditions = null) {
		return $this->create($model, $fields, $values);
	}

	/**
	 * Implement the D in CRUD. Calls to ``Model::delete()`` arrive here.
	 */
	public function delete(Model $model, $id = null) {
		$json = $this->Http->get('http://example.com/api/remove.json', array(
			'id' => $id[$model->alias . '.id'],
			'apiKey' => $this->config['apiKey'],
		));
		$res = json_decode($json, true);
		if (is_null($res)) {
			$error = json_last_error();
			throw new CakeException($error);
		}
		return true;
	}

	private function __error($message) {
		throw new RSCSourceException($message);
	}

}
