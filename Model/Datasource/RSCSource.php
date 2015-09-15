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
		if (!defined('RACKSPACE_US')) {
			// difficult finding these files on some systems
			require_once(APP . 'Plugin/RSC/Vendor/php-opencloud/lib/php-opencloud.php');
			require_once(APP . 'Plugin/RSC/Vendor/php-opencloud/lib/OpenCloud/Globals.php');
		}
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
	* Strip out the model alias out of the conditions array.
	*/
	protected function _stripOutModelAliasInConditions($conditions = array(), $alias = null) {
		if (empty($conditions) || empty($alias)) {
			return $conditions;
		}
		$retval = array();
		foreach ($conditions as $key => $value) {
			$new_key = str_replace($alias . '.', '', $key);
			$retval[$new_key] = $value;
		}
		return $retval;
	}

	protected function _error($message) {
		throw new RSCSourceException($message);
	}
}
