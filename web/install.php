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
 * Install script for TYPO3. This file will be renamed after installation!
 *
 * @author Kai Vogel <kai.vogel@e-net.info>
 */

/**
 * Path to the source directory
 */
$sourceDirectory = '../typo3_src-6.1.0/';

/**
 * Define the constant "TYPO3_PACKAGE_CONFIGURATION_FILE" with a path
 * to your own php file to extend default configuration. The file must
 * return an array of configuration values.
 */
// define('TYPO3_PACKAGE_CONFIGURATION_FILE', '...');

require $sourceDirectory . 'typo3/sysext/install/Classes/Installation/InstallationWizard.php';
$installationWizard = new \TYPO3\CMS\Install\Installation\InstallationWizard();
$installationWizard->execute($sourceDirectory, dirname(__FILE__));

?>