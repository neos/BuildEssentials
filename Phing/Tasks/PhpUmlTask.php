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
require_once('PHP/UML.php');

/**
 * PHP_UML task for Phing
 *
 */
class PhpUmlTask extends Task {

	/**
	 * @var string
	 */
	protected $input;

	/**
	 * @var string
	 */
	protected $output;

	/**
	 * @var string
	 */
	protected $title;

	/**
	 * @param string $path
	 * @return void
	 */
	public function setInput($path) {
		$this->input = $path;
	}

	/**
	 * @param string $output
	 * @return void
	 */
	public function setOutput($output) {
		$this->output = $output;
	}

	/**
	 * @param string $title
	 * @return void
	 */
	public function setTitle($title) {
		$this->title = $title;
	}

	/**
	 * @return void
	 */
	public function main() {
		$this->log('Calling PHP_UML on ' . $this->input);
		$renderer = new PHP_UML();
		$renderer->deploymentView = FALSE;
		$renderer->onlyApi = TRUE;
		$renderer->setInput($this->input);
		$renderer->parse($this->title);
		$renderer->generateXMI(2.1, 'utf-8');
		$renderer->export('html', $this->output);
	}

}

?>