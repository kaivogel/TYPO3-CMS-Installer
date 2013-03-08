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

/**
 * Prerequisite builder for the TYPO3 installation
 *
 * This script is internal code and subject to change.
 * DO NOT use it in own code, or be prepared your code might
 * break in future core versions.
 *
 * @author Kai Vogel <kai.vogel@e-net.info>
 */
class PrerequisiteBuilder {

	/**
	 * @var \TYPO3\CMS\Install\Installation\PrerequisiteBuilder
	 */
	static protected $instance = NULL;

	/**
	 * @var string
	 */
	protected $folderCreateMask = '0755';

	/**
	 * @var string
	 */
	protected $fileCreateMask = '0644';

	/**
	 * @var array
	 */
	protected $directories = array(
		'fileadmin',
		'fileadmin/_temp_',
		'fileadmin/user_upload',
		'typo3conf',
		'typo3conf/ext',
		'typo3conf/l10n',
		'typo3temp',
		'uploads',
		'uploads/media',
		'uploads/pics',
		'uploads/tf',
	);

	/**
	 * @var array
	 */
	protected $symlinks = array(
		't3lib' => 'typo3_src/t3lib',
		'typo3' => 'typo3_src/typo3',
	);

	/**
	 * @var array
	 */
	protected $defaultConfiguration = array(
		'BE' => array(
			'installToolPassword' => 'bacb98acf97e0b6112b1d1b650b84971',
		),
		'EXT' => array(
			'extListArray' => array(
				'0' => 'info',
				'1' => 'perm',
				'2' => 'func',
				'3' => 'filelist',
				'4' => 'extbase',
				'5' => 'fluid',
				'6' => 'about',
				'7' => 'version',
				'8' => 'tsconfig_help',
				'9' => 'context_help',
				'10' => 'extra_page_cm_options',
				'11' => 'impexp',
				'12' => 'sys_note',
				'13' => 'tstemplate',
				'14' => 'tstemplate_ceditor',
				'15' => 'tstemplate_info',
				'16' => 'tstemplate_objbrowser',
				'17' => 'tstemplate_analyzer',
				'18' => 'func_wizards',
				'19' => 'wizard_crpages',
				'20' => 'wizard_sortpages',
				'21' => 'lowlevel',
				'22' => 'install',
				'23' => 'belog',
				'24' => 'beuser',
				'25' => 'aboutmodules',
				'26' => 'setup',
				'27' => 'taskcenter',
				'28' => 'info_pagetsconfig',
				'29' => 'viewpage',
				'30' => 'rtehtmlarea',
				'31' => 'css_styled_content',
				'32' => 't3skin',
				'33' => 't3editor',
				'34' => 'reports',
				'35' => 'felogin',
				'36' => 'form',
			),
		),
		'SYS' => array(
			'sitename' => 'New TYPO3 site',
		),
	);

	/**
	 * @var string
	 */
	protected $sourceDirectory;

	/**
	 * @var string
	 */
	protected $workingDirectory;

	/**
	 * Return 'this' as singleton
	 *
	 * @return \TYPO3\CMS\Install\Installation\PrerequisiteBuilder
	 * @internal This is not a public API method, do not use in own extensions
	 */
	static public function getInstance() {
		if (is_null(self::$instance)) {
			self::$instance = new \TYPO3\CMS\Install\Installation\PrerequisiteBuilder();
		}
		return self::$instance;
	}

	/**
	 * Build all prerequisites for an initial installation
	 *
	 * @param string $sourceDirectory The source directory
	 * @param string $workingDirectory The working directory
	 * @return void
	 */
	static public function buildInitialEnvironment($sourceDirectory, $workingDirectory) {
		static::getInstance()
			->setSourceDirectory($sourceDirectory)
			->setWorkingDirectory($workingDirectory)
			->createDirectoryStructure()
			->createOrReplaceSourceSymlink()
			->createSymlinks()
			->createHtaccessFile()
			->createLocalConfigurationFile()
			->createFirstInstallFile();
	}

	/**
	 * Build all prerequisites for an update
	 *
	 * @param string $sourceDirectory The source directory
	 * @param string $workingDirectory The working directory
	 * @return void
	 */
	static public function updateEnvironment($sourceDirectory, $workingDirectory) {
		static::getInstance()
			->setSourceDirectory($sourceDirectory)
			->setWorkingDirectory($workingDirectory)
			->createOrReplaceSourceSymlink();
	}

	/**
	 * Set absolute path of the source directory
	 *
	 * @param string $sourceDirectory The path
	 * @return \TYPO3\CMS\Install\Installation\PrerequisiteBuilder
	 */
	public function setSourceDirectory($sourceDirectory) {
		$this->sourceDirectory = realpath($sourceDirectory) . '/';
		return $this;
	}

	/**
	 * Set absolute path of the working directory
	 *
	 * @param string $workingDirectory The path
	 * @return \TYPO3\CMS\Install\Installation\PrerequisiteBuilder
	 */
	public function setWorkingDirectory($workingDirectory) {
		$this->workingDirectory = realpath($workingDirectory) . '/';
		return $this;
	}

	/**
	 * Create all required directories
	 *
	 * @return \TYPO3\CMS\Install\Installation\PrerequisiteBuilder
	 */
	public function createDirectoryStructure() {
		foreach ($this->directories as $directory) {
			$path = $this->workingDirectory . trim($directory, '/ ');
			if (@file_exists($path) === FALSE) {
				$created = @mkdir($path, octdec($this->folderCreateMask));
				if ($created || is_dir($path)) {
					$this->createIndexHtmlFile($directory);
				}
			}
		}
		return $this;
	}

	/**
	 * Create symlink to source directory
	 *
	 * @return \TYPO3\CMS\Install\Installation\PrerequisiteBuilder
	 */
	public function createOrReplaceSourceSymlink() {
		$targetPath = $this->workingDirectory . 'typo3_src';
		if (@file_exists($targetPath) !== FALSE) {
			@unlink($targetPath);
		}
		$this->createLink($this->sourceDirectory, $targetPath);
		return $this;
	}

	/**
	 * Create all required symlinks
	 *
	 * @return \TYPO3\CMS\Install\Installation\PrerequisiteBuilder
	 */
	public function createSymlinks() {
		foreach ($this->symlinks as $targetPath => $sourcePath) {
			$this->createLink(
				$this->workingDirectory . $sourcePath,
				$this->workingDirectory . $targetPath
			);
		}
		return $this;
	}

	/**
	 * Create symlink to _.htaccess file
	 *
	 * @return \TYPO3\CMS\Install\Installation\PrerequisiteBuilder
	 */
	public function createHtaccessFile() {
		$filename = $this->workingDirectory . '_.htaccess';
		if (stripos($_SERVER['SERVER_SOFTWARE'], 'apache') !== FALSE) {
			$filename = $this->workingDirectory . '.htaccess';
		}
		$this->createLink($this->sourceDirectory . '_.htaccess', $filename);
		return $this;
	}

	/**
	 * Create LocalConfiguration.php
	 *
	 * Define the constant "TYPO3_PACKAGE_CONFIGURATION_FILE" with a path
	 * to your own php file to extend default configuration. The file must
	 * return an array of configuration values:
	 *
	 * <?php
	 * return array(...);
	 * ?>
	 *
	 * @return \TYPO3\CMS\Install\Installation\PrerequisiteBuilder
	 */
	public function createLocalConfigurationFile() {
		$filename = $this->workingDirectory . 'typo3conf/LocalConfiguration.php';
		$configuration = $this->defaultConfiguration;
		if (defined('TYPO3_PACKAGE_CONFIGURATION_FILE') && @file_exists(TYPO3_PACKAGE_CONFIGURATION_FILE) !== FALSE) {
			$additionalConfiguration = include TYPO3_PACKAGE_CONFIGURATION_FILE;
			if (is_array($additionalConfiguration)) {
				$configuration = array_merge_recursive($configuration, $additionalConfiguration);
			}
		}
		$content = $this->exportArrayToString($configuration);
		$this->writeFile($filename, "<?php\nreturn " . trim($content, "\n") . ";\n?>");
		return $this;
	}

	/**
	 * Create FIRST_INSTALLATION file in /typo3conf/
	 *
	 * @return \TYPO3\CMS\Install\Installation\PrerequisiteBuilder
	 */
	public function createFirstInstallFile() {
		$quickstartFile = $this->workingDirectory . 'typo3conf/FIRST_INSTALL';
		$enableInstallToolFile = $this->workingDirectory . 'typo3conf/ENABLE_INSTALL_TOOL';
		if (!is_file($quickstartFile) && !is_file($enableInstallToolFile)) {
			$this->writeFile($quickstartFile, '');
		}
		return $this;
	}

	/**
	 * Create index.html with a redirect to base url in given directory
	 *
	 * @param string $targetPath Path to target directory
	 * @return void
	 */
	protected function createIndexHtmlFile($targetPath) {
		$filename = $this->workingDirectory . trim($targetPath, '/ ') . '/index.html';
		$content = '
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 3.2 Final//EN">
<HTML>
<HEAD>
<TITLE></TITLE>
<META http-equiv=Refresh Content="0; Url=/">
</HEAD>
</HTML>
		';
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
		$sourcePath = realpath($sourcePath);
		if (@file_exists($sourcePath) !== FALSE && @file_exists($targetPath) === FALSE) {
			return @symlink($sourcePath, $targetPath);
		}
		return FALSE;
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
			file_put_contents($filename, trim($content, "\n"));
			@chmod($filename, octdec($this->fileCreateMask));
		}
	}

	/**
	 * Export an array into string
	 *
	 * @param array $array The array
	 * @return string Array as string
	 */
	protected function exportArrayToString(array $array) {
		$replacements = array(
			"| =>[\s\n]*array \(|" => " => array(",
			"|(\d{1,}) =>|" => "'$1' =>",
			"| {2}|" => "\t",
		);
		ob_start();
		var_export($array);
		$string = ob_get_clean();
		return preg_replace(array_keys($replacements), array_values($replacements), $string);
	}

}
?>