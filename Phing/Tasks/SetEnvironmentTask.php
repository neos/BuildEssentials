<?php

/*                                                                        *
 * This script belongs to the TYPO3 Flow build system.                    *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

require_once('phing/Task.php');

/**
 * SetEnvironment task for Phing
 *
 */
class SetEnvironmentTask extends Task {

	/**
	 * @var string
	 */
	protected $name;

	/**
	 * @var string
	 */
	protected $value;

	/**
	 * @param string $name
	 * @return void
	 */
	public function setName($name) {
		$this->name = $name;
	}

	/**
	 * @param string $value
	 * @return void
	 */
	public function setValue($value) {
		$this->value = $value;
	}

	/**
	 * Sets the environment variable specified by name and value.
	 *
	 * @return void
	 */
	public function main() {
		$this->log('Calling SetEnvironment (' . $this->name .'=' . $this->value . ')');
		putenv("$this->name=$this->value");

		$_SERVER[$this->name] = $this->value;
		$_ENV[$this->name] = $this->value;
	}

}

?>