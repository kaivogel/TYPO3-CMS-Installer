<?php
namespace TYPO3\CMS\Install\Installation;

/***********************************************************************
 *  Copyright notice
 *
 *  (c) 2013 Kai Vogel <kai.vogel@e-net.info>, e-net Development Stuttgart UG (haftungsbeschränkt)
 *
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 **********************************************************************/

require 'PrerequisiteBuilder.php';

/**
 * TYPO3 installation wizard
 *
 * This script is internal code and subject to change.
 * DO NOT use it in own code, or be prepared your code might
 * break in future core versions.
 *
 * @author Kai Vogel <kai.vogel@e-net.info>
 */
class InstallationWizard {

	/**
	 * @var string
	 */
	protected $sourceDirectory;

	/**
	 * @var string
	 */
	protected $workingDirectory;

	/**
	 * @var array
	 */
	protected $cssFiles = array(
		'typo3/sysext/install/Resources/Public/Stylesheets/reset.css',
		'typo3/sysext/install/Resources/Public/Stylesheets/general.css',
		'typo3/sysext/install/Resources/Public/Stylesheets/install_123.css',
	);

	/**
	 * @var array
	 */
	protected $jsFiles = array(
		'typo3/contrib/prototype/prototype.js',
		'typo3/sysext/install/Resources/Public/Javascript/install.js',
	);

	/**
	 * @var string
	 */
	protected $html = '
		<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
		<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
			<head>
				<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
				<link rel="shortcut icon" href="@favicon" />
				<title>Installing TYPO3 @version</title>
				<style type="text/css">@css</style>
			</head>
			<body class="standalone">
				<div id="t3-install-outer">
					<h1>TYPO3 @version</h1>
					<div id="t3-install-box-border-top">&nbsp;</div>
					<h2>Installing TYPO3 @version</h2>
					<div id="t3-install-box-body">
						@content
					</div>
					<div id="t3-install-box-border-bottom">&nbsp;</div>
					<div id="t3-install-copyright">
						<p>
							<strong>TYPO3 CMS.</strong> Copyright © @copyright
							Kasper Skårhøj. Extensions are copyright of their respective
							owners. Go to <a href="http://typo3.org/">http://typo3.org/</a>
							for details. TYPO3 comes with ABSOLUTELY NO WARRANTY;
							<a href="http://typo3.org/licenses">click</a> for details.
							This is free software, and you are welcome to redistribute it
							under certain conditions; <a href="http://typo3.org/licenses">click</a>
							for details. Obstructing the appearance of this notice is prohibited by law.
						</p>
						<p>
							<a href="http://typo3.org/donate/online-donation/"><strong>Donate</strong></a> |
							<a href="http://typo3.org/">TYPO3.org</a>
						</p>
					</div>
				</div>
				<script type="text/javascript">@js</script>
			</body>
		</html>
	';

	/**
	 * Execute installation
	 *
	 * @param string $sourceDirectory Path to the source directory
	 * @param string $workingDirectory Target path for new files
	 * @return void
	 */
	public function execute($sourceDirectory, $workingDirectory) {
		$this->sourceDirectory = realpath($sourceDirectory) . '/';
		$this->workingDirectory = realpath($workingDirectory) . '/';
		// Show system check result and installation button
		if (!isset($_POST['install'])) {
			$content = $this->checkEnvironment();
			$content .= $this->getNextButton();
			$this->printContent($content);
			return;
		}
		// Update / install
		if (@file_exists($this->workingDirectory . 'typo3_src') === FALSE) {
			PrerequisiteBuilder::buildInitialEnvironment($this->sourceDirectory, $this->workingDirectory);
		} else {
			PrerequisiteBuilder::updateEnvironment($this->sourceDirectory, $this->workingDirectory);
		}
		// Disable installer and execute default bootstrap
		$this->renameInstallerAndRedirect();
	}

	/**
	 * Check the environment
	 *
	 * @return string HTML result of the check
	 */
	protected function checkEnvironment() {
		require $this->sourceDirectory . 'typo3/sysext/install/Classes/SystemEnvironment/Check.php';
		/** @var $statusCheck \TYPO3\CMS\Install\SystemEnvironment\Check */
		$statusCheck = new \TYPO3\CMS\Install\SystemEnvironment\Check();
		$statusObjects = $statusCheck->getStatus();
		$content = '<h3>Environment Check Status</h3>';
		if (is_array($statusObjects) && !empty($statusObjects)) {
			/** @var $statusObject \TYPO3\CMS\Install\SystemEnvironment\AbstractStatus */
			foreach ($statusObjects as $statusObject) {
				switch (get_class($statusObject)) {
					case 'TYPO3\CMS\Install\SystemEnvironment\ErrorStatus':
						$cssClass = 'error';
					break;
					case 'TYPO3\CMS\Install\SystemEnvironment\WarningStatus':
						$cssClass = 'warning';
						break;
					case 'TYPO3\CMS\Install\SystemEnvironment\OkStatus':
						$cssClass = 'ok';
						break;
					case 'TYPO3\CMS\Install\SystemEnvironment\InfoStatus':
						$cssClass = 'information';
						break;
					case 'TYPO3\CMS\Install\SystemEnvironment\NoticeStatus':
						default:
					$cssClass = 'notice';
						break;
				}
				$content .= '
					<div class="typo3-message message-' . $cssClass . '">
						<div class="header-container">
							<div class="message-header message-left">' . $statusObject->getTitle() . '</div>
							<div class="message-header message-right"></div>
						</div >
						<div class="message-body">' . $statusObject->getMessage() . '</div>
					</div ><br />';
			}
		}
		return $content;
	}

	/**
	 * Get HTML content of the next button
	 *
	 * @return void
	 */
	protected function getNextButton() {
		$html = '
			<form action="install.php" method="post">
				<fieldset class="t3-install-form-submit">
					<ol>
						<li>
							<button name="install" type="submit">
								Proceed with installation
								<span class="t3-install-form-button-icon-positive">&nbsp;</span>
							</button>
						</li>
						<li>
							<strong>NOTICE:</strong>
							By clicking this button, all required directories and files will be created in current directory!
						</li>
					</ol>
				</fieldset>
			</form>
		';
		return $html;
	}

	/**
	 * Rename install.php to disable further installations and redirect
	 * to index.php
	 *
	 * @return void
	 */
	protected function renameInstallerAndRedirect() {
		if (@file_exists($this->workingDirectory . '_install.php') !== FALSE) {
			@unlink($this->workingDirectory . '_install.php');
		}
		rename(
			$this->workingDirectory . 'install.php',
			$this->workingDirectory . '_install.php'
		);
		header('Location: index.php');
		exit;
	}

	/**
	 * Wrap content with HTML layout and print
	 *
	 * @param string $content The content to wrap
	 * @return void
	 */
	protected function printContent($content) {
		$replacements = array(
			'@favicon'   => $this->getDataImage($this->sourceDirectory . 'typo3/gfx/favicon.ico'),
			'@version'   => $this->getVersionFromSourceDirectory(),
			'@css'       => $this->getCss(),
			'@content'   => $content,
			'@copyright' => '1998-' . date('Y'),
			'@js'        => $this->getJs(),
		);
		$html = str_replace(
			array_keys($replacements),
			array_values($replacements),
			trim($this->html, "\n")
		);
		header('Content-Type: text/html; charset=utf-8');
		echo $html;
	}

	/**
	 * Get content of required stylesheet files
	 *
	 * @return string Stylesheet content
	 */
	protected function getCss() {
		$content = '';
		foreach ($this->cssFiles as $filename) {
			$content .= "\n" . file_get_contents($this->sourceDirectory . $filename);
		}
		$content = preg_replace_callback('|url\(([^)]*)\)|', array($this, 'getCssReplaceCallback'), $content);
		return $content;
	}

	/**
	 * Callback method for image replacements in css content
	 *
	 * @param array $matches Regex matches
	 * @return string Replacement string
	 */
	protected function getCssReplaceCallback(array $matches) {
		if (empty($matches[1])) {
			return "url('')";
		}
		$filename = trim($matches[1], '"\' ');
		$replacements = array(
			'../Images/' => $this->sourceDirectory . 'typo3/sysext/install/Resources/Public/Images/',
			'../../../../../gfx/' => $this->sourceDirectory . 'typo3/gfx/',
		);
		$filename = str_replace(array_keys($replacements), array_values($replacements), $filename);
		return "url('" . $this->getDataImage($filename) . "')";
	}

	/**
	 * Get content of required javascript files
	 *
	 * @return string JavaScript content
	 */
	protected function getJs() {
		$content = '';
		foreach ($this->jsFiles as $filename) {
			$content .= "\n" . file_get_contents($this->sourceDirectory . $filename);
		}
		return $content;
	}

	/**
	 * Get data:image from image filename
	 *
	 * @param string $filename Filename of the image
	 * @return string Data url of the image
	 */
	protected function getDataImage($filename) {
		if (@file_exists($filename) !== FALSE) {
			$data = base64_encode(file_get_contents($filename));
			$type = substr($filename, strrpos($filename, '.') + 1);
			return 'data:image/' . $type . ';base64,' . $data;
		}
		return '';
	}

	/**
	 * Return the TYPO3 version from source path
	 *
	 * @return string Version string
	 */
	protected function getVersionFromSourceDirectory() {
		if (is_string($this->sourceDirectory)) {
			preg_match('|\d\.\d\.?\d?|', $this->sourceDirectory, $matches);
			return $matches[0];
		}
		return '';
	}

}
?>