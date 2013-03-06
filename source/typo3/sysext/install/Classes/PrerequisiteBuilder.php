<?php
namespace TYPO3\CMS\Install;

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
 * Prerequisite builder for the
 *
 * This script is internal code and subject to change.
 * DO NOT use it in own code, or be prepared your code might
 * break in future core versions.
 *
 * @author Kai Vogel <kai.vogel@e-net.info>
 */
class PrerequisiteBuilder {

	/**
	 * @var \TYPO3\CMS\Install\PrerequisiteBuilder
	 */
	static protected $instance = NULL;

	/**
	 * @var array
	 */
	protected $directories = array(
		'fileadmin' => array(
			'_temp_' => NULL,
			'user_upload' => NULL,
		),
		'typo3conf' => array(
			'ext' => NULL,
			'l10n' => NULL,
		),
		'typo3temp' => NULL,
		'uploads' => array(
			'media' => NULL,
			'pics' => NULL,
			'tf' => NULL,
		),
	);

	/**
	 * @var array
	 */
	protected $symlinks = array(
		'typo3_src'         => '@source',
		't3lib'             => 'typo3_src/t3lib',
		'typo3'             => 'typo3_src/typo3',
		'index.php'         => 'typo3_src/index.php',
		'INSTALL.txt'       => 'typo3_src/INSTALL.txt',
		'README.txt'        => 'typo3_src/README.txt',
		'RELEASE_NOTES.txt' => 'typo3_src/RELEASE_NOTES.txt',
	);

	/**
	 * @var integer
	 */
	protected $directoryAccessMode = 777;

	/**
	 * @var integer
	 */
	protected $fileAccessMode = 664;

	/**
	 * @var string
	 */
	protected $lastDirectory;

	/**
	 * Return 'this' as singleton
	 *
	 * @return \TYPO3\CMS\Install\PrerequisiteBuilder
	 * @internal This is not a public API method, do not use in own extensions
	 */
	static public function getInstance() {
		if (is_null(self::$instance)) {
			self::$instance = new \TYPO3\CMS\Install\PrerequisiteBuilder();
		}
		return self::$instance;
	}

	/**
	 * Build all prerequisites at once
	 *
	 * @return void
	 */
	static public function buildAll() {
		static::getInstance()
			->createDirectoryStructure()
			->createSymlinks()
			->createHtaccessFile()
			->createExtTablesFile()
			->createLocalConfigurationFile()
			->createFirstInstallFile();
	}

	/**
	 * Create all required directories
	 *
	 * @return \TYPO3\CMS\Install\PrerequisiteBuilder
	 */
	public function createDirectoryStructure() {
		$this->createRecursiveDirectories($this->directories, $this->directoryAccessMode);
		return $this;
	}

	/**
	 * Create all required symlinks
	 *
	 * @return \TYPO3\CMS\Install\PrerequisiteBuilder
	 */
	public function createSymlinks() {
		foreach ($this->symlinks as $targetPath => $sourcePath) {
			if ($sourcePath === '@source') {
				$sourcePath = 'typo3_src-' . TYPO3_version;
			}
			$this->createLink(PATH_site . $sourcePath, PATH_site . $targetPath);
		}
		return $this;
	}

	/**
	 * Create _.htaccess file
	 *
	 * TODO: If Apache -> write .htaccess instead of _.htaccess
	 *
	 * @return \TYPO3\CMS\Install\PrerequisiteBuilder
	 */
	public function createHtaccessFile() {
		$filename = PATH_site . '_.htaccess';
		$content = 'TODO';
		$this->writeFile($filename, $content);
		return $this;
	}

	/**
	 * Create extTables.php
	 *
	 * @return \TYPO3\CMS\Install\PrerequisiteBuilder
	 */
	public function createExtTablesFile() {
		$filename = PATH_typo3conf . 'extTables.php';
		$content = 'TODO';
		$this->writeFile($filename, $content);
		return $this;
	}

	/**
	 * Create LocalConfiguration.php
	 *
	 * @return \TYPO3\CMS\Install\PrerequisiteBuilder
	 */
	public function createLocalConfigurationFile() {
		$filename = PATH_typo3conf . 'LocalConfiguration.php';
		$content = 'TODO';
		$this->writeFile($filename, $content);
		return $this;
	}

	/**
	 * Create FIRST_INSTALLATION file in /typo3conf/
	 *
	 * @return \TYPO3\CMS\Install\PrerequisiteBuilder
	 */
	public function createFirstInstallFile() {
		$quickstartFile = PATH_typo3conf . 'FIRST_INSTALL';
		$enableInstallToolFile = PATH_typo3conf . 'ENABLE_INSTALL_TOOL';
		if (!is_file($quickstartFile) && !is_file($enableInstallToolFile)) {
			$this->writeFile($quickstartFile, '');
		}
		return $this;
	}

	/**
	 * Create recursive directories
	 *
	 * @param array $directories Directory structure
	 * @param integer $accessMode Directory access mode
	 * @return void
	 */
	protected function createRecursiveDirectories(array $directories, $accessMode = 770) {
		foreach ($directories as $name => $content) {
			$path = PATH_site . trim($name, '/ ');
			if (@file_exists($path) === FALSE) {
				$created = mkdir($path, $accessMode);
				if ($created || is_dir($path)) {
					$this->createIndexHtmlFile($path);
				}
			}
			if (is_array($content)) {
				$this->createRecursiveDirectories($content, $accessMode);
			}
		}
	}

	/**
	 * Create index.html with a redirect to base url in given directory
	 *
	 * @param string $targetPath Path to target directory
	 * @return void
	 */
	protected function createIndexHtmlFile($targetPath) {
		$filename = PATH_site . trim($targetPath, '/ ') . '/index.html';
		$content = '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 3.2 Final//EN">
<HTML>
<HEAD>
<TITLE></TITLE>
<META http-equiv=Refresh Content="0; Url=/">
</HEAD>
</HTML>';
		$this->writeFile($filename, $content);
	}

	/**
	 * Create a link / symlink
	 *
	 * The PHP function "symlink" will only work on Windows Vista,
	 * Windows Server 2008 or newer. Windows versions prior to that
	 * does not support symbolic links.
	 *
	 * @param string $sourcePath Path to source file or directory
	 * @param string $targetPath Path to target directory
	 * @return boolean TRUE on success
	 */
	protected function createLink($sourcePath, $targetPath) {
		return symlink($sourcePath, $targetPath);
	}

	/**
	 * Create a file with correct access mode
	 *
	 * @param string $filename The filename
	 * @param string $content File content
	 * @return void
	 */
	protected function writeFile($filename, $content) {
		if (@file_exists($filename) === FALSE) {
			file_put_contents($filename, $content);
			chmod($filename, $this->fileAccessMode);
		}
	}

}
?>