<?php
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

/**
 * Installer script for TYPO3. This file will be removed after installation!
 *
 * @author Kai Vogel <kai.vogel@e-net.info>
 */
class Install {

	/**
	 * @var string
	 */
	static protected $version = '6.1';

	/**
	 * @var string
	 */
	static protected $sourceDirectory = '../typo3_src-6.1.0/';

	/**
	 * @var string
	 */
	static protected $copyrightYear = '1998-2013';

	/**
	 * @var array
	 */
	static protected $cssFiles = array(
		'typo3/sysext/install/Resources/Public/Stylesheets/reset.css',
		'typo3/sysext/install/Resources/Public/Stylesheets/general.css',
		'typo3/sysext/install/Resources/Public/Stylesheets/install_123.css',
	);

	/**
	 * @var array
	 */
	static protected $jsFiles = array(
		'typo3/contrib/prototype/prototype.js',
		'typo3/sysext/install/Resources/Public/Javascript/install.js',
	);

	/**
	 * @var string
	 */
	static protected $html = '
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
	 * @return void
	 */
	static public function execute() {
		if (!isset($_POST['buildPrerequisites'])) {
			$content = static::checkEnvironment();
			$content .= static::getNextButton();
			static::printContent($content);
		} else {
			static::buildPrerequisites();
			static::renameInstallerAndRedirect();
		}
	}

	/**
	 * Check the environment
	 *
	 * @return string HTML result of the check
	 */
	static protected function checkEnvironment() {
		require static::getSourcePath('typo3/sysext/install/Classes/SystemEnvironment/Check.php');
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
	static protected function getNextButton() {
		$html = '
			<form action="install.php" method="post">
				<fieldset class="t3-install-form-submit">
					<ol>
						<li>
							<button name="buildPrerequisites" type="submit">
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
	 * Build prerequisites
	 *
	 * @return void
	 */
	static protected function buildPrerequisites() {
		require static::getSourcePath('typo3/sysext/install/Classes/PrerequisiteBuilder.php');
		\TYPO3\CMS\Install\PrerequisiteBuilder::buildAll(static::$sourceDirectory, dirname(__FILE__));
	}

	/**
	 * Redirect to fresh install tool
	 *
	 * @return void
	 */
	static protected function renameInstallerAndRedirect() {
		rename('install.php', '_install.php');
		header('Location: index.php');
		exit;
	}

	/**
	 * Wrap content with HTML layout and print
	 *
	 * @param string $content The content to wrap
	 * @return void
	 */
	static protected function printContent($content) {
		$faviconFile = static::getSourcePath('typo3/gfx/favicon.ico');
		$replacements = array(
			'@favicon'   => static::getDataImage($faviconFile),
			'@version'   => static::$version,
			'@css'       => static::getCss(),
			'@content'   => $content,
			'@copyright' => static::$copyrightYear,
			'@js'        => static::getJs(),
		);
		$html = str_replace(
			array_keys($replacements),
			array_values($replacements),
			trim(static::$html, "\n")
		);
		header('Content-Type: text/html; charset=utf-8');
		echo $html;
	}

	/**
	 * Get content of required stylesheet files
	 *
	 * @return string Stylesheet content
	 */
	static protected function getCss() {
		$content = '';
		foreach (static::$cssFiles as $filename) {
			$content .= "\n" . file_get_contents(static::getSourcePath($filename));
		}
		$content = preg_replace_callback('|url\(([^)]*)\)|', 'static::getCssReplaceCallback', $content);
		return $content;
	}

	/**
	 * Callback method for image replacements in css content
	 *
	 * @param array $matches Regex matches
	 * @return string Replacement string
	 */
	static protected function getCssReplaceCallback(array $matches) {
		if (empty($matches[1])) {
			return "url('')";
		}
		$filename = trim($matches[1], '"\' ');
		$replacements = array(
			'../Images/' => static::getSourcePath('typo3/sysext/install/Resources/Public/Images/'),
			'../../../../../gfx/' => static::getSourcePath('typo3/gfx/'),
		);
		$filename = str_replace(array_keys($replacements), array_values($replacements), $filename);
		return "url('" . static::getDataImage($filename) . "')";
	}

	/**
	 * Get content of required javascript files
	 *
	 * @return string JavaScript content
	 */
	static protected function getJs() {
		$content = '';
		foreach (static::$jsFiles as $filename) {
			$content .= "\n" . file_get_contents(static::getSourcePath($filename));
		}
		return $content;
	}

	/**
	 * Get data:image from image filename
	 *
	 * @param string $filename Filename of the image
	 * @return string Data url of the image
	 */
	static protected function getDataImage($filename) {
		if (@file_exists($filename) !== FALSE) {
			$data = base64_encode(file_get_contents($filename));
			$type = substr($filename, strrpos($filename, '.') + 1);
			return 'data:image/' . $type . ';base64,' . $data;
		}
		return '';
	}

	/**
	 * Returns the full relative path of a file
	 *
	 * @param string $filename The filename relative to source directory
	 * @return string The relative path
	 */
	static protected function getSourcePath($filename) {
		return rtrim(static::$sourceDirectory, '/') . '/' . $filename;
	}

}

// Execute installation...
Install::execute();
?>