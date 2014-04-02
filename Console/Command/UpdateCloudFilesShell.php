<?php
/**
 * Update Cloud Files Shell
 *
 *
 *
 *
 */
App::uses('RSCFile', 'RSC.Model');
App::uses('CakeSchema', 'Model');
class UpdateCloudFilesShell extends Shell {
	public $uses = array();
	
	public $RSC = null;
	public $config = array();
	// public $containerName = 'ao-dev-assets';
	public $containerName = 'ao-qa-assets';

	/**
	 * Main worker
	 */
	public function main() {
		$this->RSCFile = ClassRegistry::init('RSC.RSCFile');
		$this->RSCFile->useDbConfig = 'rsc';

		$files_changed = $this->changeContentDisposition($this->containerName, 'mp3', 'attachment');
		
		var_dump($files_changed);
		print "Updated " . count($files_changed) . " files.\n";

	}

	public function changeContentDisposition($container_name, $file_type_to_change, $new_disposition) {
		$container = $this->RSCFile->container($container_name);
		$files = $this->RSCFile->findFilesWithDetails('', $container); // only gets 10,000?
		$updated_files = array();
		foreach ($files as $file) {
			if (strpos($file['type'], $file_type_to_change) !== false) {
				$params = array('extra_headers' => array('Content-Disposition' => "{$new_disposition}; filename={$file['name']}"));
				$this->RSCFile->update($file['name'], $params);
				$updated_files[] = $file['name'];
			}
		}
		return $updated_files;
	}
}

