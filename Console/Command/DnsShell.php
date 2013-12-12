<?php
/**
 * Helpful shell to RSC Domain management
 *
 */
class DnsShell extends Shell{

	/**
	 * Load these models
	 */
	public $uses = array(
		'RSC.RSCDomain',
		'RSC.RSCRecord',
	);

	/**
	 * Run all parts and peices
	 */
	public function main() {
		$target = array_shift($this->args);
		$source = array_shift($this->args);
		if (empty($target) || empty($source)) {
			$this->help();
			return;
		}
		$this->record($target, $source);
	}

	/**
	 * Help
	 */
	public function help() {
		$this->out('RSC DNS Help');
		$this->out();
		$this->out("  ./cake RSC.dns <domain> <ip>");
		$this->out("    ./cake RSC.dns something.example.com 111.111.111.111");
		$this->out("      makes an A record for the domain 'example.com'");
		$this->out();
		$this->out("  ./cake RSC.dns <domain> <cname>");
		$this->out("    ./cake RSC.dns something.example.com google.com");
		$this->out("      makes a CNAME record for the domain 'example.com'");
		$this->out();
	}

	/**
	 * Save a record, simply
	 *
	 * @param string $target
	 * @param string $source (ip or cname)
	 * @param string $ttl [3600]
	 * @return boolean
	 */
	public function record($target, $source, $ttl = '3600') {
		$record_data = $this->parseTarget($target);
		$record_data['type'] = ($this->isIP($source) ? 'CNAME' : 'A');
		$record_data['data'] = $source;
		$record_data['ttl'] = $ttl;
		$result = $this->RSCRecord->save($record_data);
		if (empty($result)) {
			$this->error("Unable to make the record [{$target} --> {$source}]");
		}
		$this->out("  Success [{$target} --> {$source}]");
		return true;
	}

	/**
	 * Validate that the zone exists or create it
	 *
	 * @param string $zone
	 * @return boolean
	 */
	public function zone($zone) {
		if ($this->RSCDomain->exists($zone)) {
			return true;
		}
		$data = array(
			'name' => $zone,
			'emailAddress' => "webmaster@$zone",
			'ttl' => 3600
		);
		$result = $this->RSCDomain->save($data);
		if (empty($result)) {
			$this->error("Unable to make the zone [{$zone}]");
		}
		return true;
	}

	/**
	 * parse a target into an array, with the zone and full name
	 *
	 * @param string $target
	 * @return array $data compact('name', 'zone')
	 */
	public function parseTarget($target) {
		$target = trim($target);
		$parts = explode('.', $target);
		$tld = array_pop($parts);
		$zone = array_pop($parts) . '.' . $tld;
		return array(
			'name' => $target,
			'zone' => $zone,
		);
	}

	/**
	 *
	 *
	 */
	public function isIP($source) {
		if (empty($source) || !is_string($source)) {
			return false;
		}
		return preg_match('/^(?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)$/', $source);
	}

}
