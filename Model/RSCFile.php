<?php
/**
 * Rackspace Cloud Plugin for CakePHP
 *
 * @license MIT
 * @author Alan Blount <alan@zeroasterisk.com>
 * @link https://github.com/zeroasterisk/RSC-CakePHP-Plugin
 *
 * ----------------------------------
 *
 * This is the RSC Files / Cloud Files implementation of the plugin
 *
 * NOTE: all API configuration is done via the RSCSource DataSource: app/Config/RSC.php
 *
 * @link containers: http://docs.rackspace.com/files/api/v1/cf-devguide/content/Container_Quotas-d1e1444.html
 * @link objects/files: http://docs.rackspace.com/files/api/v1/cf-devguide/content/Storage_Objects-d1e4300.html
 *
 */
App::uses('RSCAppModel', 'RSC.Model');
class RSCFile extends RSCAppModel {

	/**
	 * placeholders stashes for existing connections
	 */
	public $containers = array();
	
	/**
	 * If we want to create() or update() we need to specify the fields
	 * available. We use the same array keys as we do with CakeSchema, eg.
	 * fixtures and schema migrations.
	 */
	protected $_schema = array(
		'name' => array('type' => 'string','null' => false,'key' => 'primary','length' => 255),
		'size' => array('type' => 'int','null' => false,'length' => 11),
		'type' => array('type' => 'string','null' => true,'length' => 255),
		'publicurl' => array('type' => 'string','null' => true,'length' => 255),
		'cdnurl' => array('type' => 'string','null' => true,'length' => 255),
		'hash' => array('type' => 'string','null' => true,'length' => 255),
		'last_modified' => array('type' => 'datetime','null' => true),
		'content_type' => array('type' => 'string','null' => false, 'length' => 255),
	);

	/**
	 * Setup RSC Files specific needs
	 *
	 */
	public function __construct($config = array()) {
		// initialize defines for the vendor lib
		if (!defined('RAXSDK_TIMEOUT')) {
			define('RAXSDK_TIMEOUT', 600);
		}
		return parent::__construct($config);
	}

	/**
	 * init the API, get and return the container
	 *
	 * @param string $containerName (if empty, get from config, default)
	 * @return object $container
	 */
	public function container($containerName = null, $options = array()) {
		$defalts = array(
			'region' => 'DFW',
		);
		$defalts = array_merge($defalts, $this->datasource()->config);
		$options = array_merge($defalts, $options);
		// verify required data
		if (empty($containerName) && !empty($options['container'])) {
			// container name now based on the options
			$containerName = $options['container'];
		}
		if (empty($containerName) && !empty($this->containers)) {
			// get last used container
			$containerName = current($this->containers);
		}
		if (empty($containerName)) {
			throw new OutOfBoundsException('RSCFile::container() missing required container name');
		}
		if (is_object($containerName)) {
			// containerName is actually the object Container... just return it
			return $containerName;
		}
		if (!empty($this->containers[$containerName])) {
			// stashed container connection/object exists... just return the stashed one
			return $this->containers[$containerName];
		}
		// Setup API connection
		$connection = $this->datasource()->connect();
		// now, connect to the ObjectStore service
		$objstore = $connection->ObjectStore('cloudFiles', $options['region']);
		if (!is_object($objstore)) {
			throw new OutOfBoundsException('RSCFile::container() unable to connect to the Rackspace Files ObjectStore');
		}
		// now get the container
		try {
			// try to assume the container exists, and simply load it
			//   this is faster and usually the case
			$this->containers[$containerName] = $objstore->Container($containerName);
		} catch (Exception $e) {
			// didn't work?  list all containers and make/load as needed
			//   slower but should always work
			$this->containers[$containerName] = $this->getOrMakeContainer($objstore, $containerName);
		}
		// return the container
		return $this->containers[$containerName];
	}

	/**
	 * Get or Make the container
	 *
	 * @param object $objstore
	 * @param array $config
	 * @return object $container
	 */
	private function getOrMakeContainer($objstore, $containerName) {
		// list all containers
		$cdncontainers = array();
		$cdnlist = $objstore->CDN()->ContainerList();
		while($cdncontainer = $cdnlist->Next()) {
			$cdncontainers[] = $cdncontainer->name;
		}
		if (in_array($containerName, $cdncontainers)) {
			// existing container
			$container = $objstore->Container($containerName);
		} else {
			// create a new container
			$container = $objstore->Container();
			$container->Create(array('name' => $containerName));
			$cdnversion = $container->PublishToCDN();
		}
		return $container;
	}

	/**
	 * Actually sends the file via the API to RSC
	 *
	 * @param string $sourcefile full path to file
	 * @param mixed $container string or object (or null = get lastused container)
	 * @param mixed $params
	 *               'name' = target file name
	 * @return object $newDataObject
	 */
	public function upload($sourcefile, $container = null, $params = array()) {
		if (is_string($params)) {
			$params = array(
				'name' => $params,
			);
		}
		if (empty($params['name'])) {
			$params['name'] = basename($sourcefile);
		}
		if (empty($params['content_type'])) {
			$params['content_type'] = $this->getContentType($sourcefile);
		}
		$config = $this->datasource()->config;
		if (!empty($config['files']['defaults'])) {
			$params = array_merge($config['files']['defaults'], $params);
		}
		// create & upload, send the file
		$newDataObject = $this->container($container)->DataObject();
		$newDataObject->Create($params, $sourcefile);
		return $newDataObject;
	}

	/**
	 * Download a file to a local filename
	 *
	 * @param string $filename or $prefix
	 *               if you don't pass $filename we try to use $this->id
	 * @param mixed $container string or object (or null = get lastused container)
	 * @param string $saveto IMPORTANT file path to save to. eg: APP . 'tmp' . DS . 'myfile.png'
	 * @return boolean
	 */
	public function download($filename, $container = null, $saveto = null) {
		$this->read(null, $filename, $container)->SaveToFilename($saveto);
		return true;
	}

	/**
	 * a super-quick function to determine if a filename exists in a container
	 *
	 * this is a janky overwrite of the Model->exists()
	 *
	 * @param string $filename or $prefix
	 *               if you don't pass $filename we try to use $this->id
	 * @param mixed $container string or object (or null = get lastused container)
	 * @return boolean
	 */
	public function exists($filename = null, $container = null) {
		if (empty($filename)) {
			$filename = $this->id;
		}
		if (empty($filename)) {
			return false;
		}
		$files = $this->findFiles($filename, $container);
		return in_array($filename, $files, true);
	}

	/**
	 * quick access overwrite for Model->read()
	 *
	 * @param string $filename or $prefix
	 *               if you don't pass $filename we try to use $this->id
	 * @param mixed $container string or object (or null = get last used container)
	 * @return object DataObject
	 */
	public function _read($fields = null, $filename = null, $container = null) {
		if (empty($filename)) {
			$filename = $this->id;
		}
		if (empty($filename)) {
			return false;
		}
		return $this->container($container)->DataObject($filename);
	}

	/**
	 * Update a file 
	 * 
	 * @param string $filename
	 * @param array $params collection of new metadata for file
	 * @param mixed $container string or object (or null = get last used container)
	 */
	public function updateFileMetadata($filename, $params, $container = null) {
		if (empty($filename)) {
			return false;
		}
		$object = $this->container($container)->DataObject($filename);
		$object->UpdateMetadata($params);
	}

	/**
	 * An API consistent overwrite of the Model->delete() method
	 *
	 * @param string $filename or $prefix
	 *               if you don't pass $filename we try to use $this->id
	 * @param mixed $container string or object (or null = get last used container)
	 * @return object DataObject
	 */
	public function delete($filename = null, $container = null) {
		if (empty($filename)) {
			$filename = $this->id;
		}
		if (empty($filename)) {
			return false;
		}
		if (is_object($filename)) {
			$object = $filename;
		} else {
			if (!$this->exists($filename, $container)) {
				return false;
			}
			$object = $this->container($container)->DataObject($filename);
		}
		if (empty($object)) {
			return false;
		}
		$object->Delete();
		return true;
	}

	/**
	 * a quick and simple search/find for a filename or prefix
	 *
	 * @param string $filename or $prefix
	 * @param mixed $container string or object (or null = get last used container)
	 * @return array $files names of files
	 */
	public function findFiles($filename, $container = null) {
		$list = $this->container($container)->ObjectList(array('prefix' => $filename));
		$files = array();
		while($o = $list->Next()) {
			$files[] = $o->name;
		}
		return $files;
	}

	/**
	 * a quick and simple search/find for a filename or prefix
	 *
	 * @param string $filename or $prefix
	 * @param mixed $container string or object (or null = get last used container)
	 * @return array $files (details, each node should have name, size, type, public_url)
	 */
	public function findFilesWithDetails($filename, $container = null) {
		$list = $this->container($container)->ObjectList(array('prefix' => $filename));
		$files = array();
		while($o = $list->Next()) {
			$files[] = array(
				'name' => $o->name,
				'size' => $o->bytes,
				'type' => $o->content_type,
				'public_url' => $o->PublicURL(),
			);
		}
		return $files;
	}

	/**
	 * Return the content/mime-type for a file
	 *
	 * @param string $sourcefile (full path to source file)
	 * @return string $contentType (mimetype)
	 */
	public function getContentType($sourcefile) {
		if (function_exists('finfo_open') && function_exists('finfo_file')) {
			$finfo = finfo_open(FILEINFO_MIME_TYPE);
			$contentType = finfo_file($finfo, $sourcefile);
			finfo_close($finfo);
			return $contentType;
		}
		if (function_exists('mime_content_type')) {
			return mime_content_type($sourcefile);
		}
		return null;
	}

	/**
	 * get the details for a file inside a container
	 * (doens't verify file exists first)
	 *
	 * @param object $container
	 * @param string $filename
	 * @return array $details
	 */
	public function getFileDetails($filename, $container = null) {
		$o = $this->_read(null, $filename, $container);
		return array(
			'name' => $o->name,
			'size' => $o->bytes,
			'cdnurl' => $o->CDNUrl(),
			'publicurl' => $o->PublicURL(),
			'hash' => $o->hash,
			'last_modified' => $o->last_modified,
			'content_type' => $o->content_type,
		);
	}

}
