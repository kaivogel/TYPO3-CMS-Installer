<?php
namespace TYPO3\CMS\Extbase\Tests\Unit\Persistence;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2012 Nico de Haen
 *  All rights reserved
 *
 *  This class is a backport of the corresponding class of FLOW3.
 *  All credits go to the v5 team.
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
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
 ***************************************************************/

class PersistenceManagerTest extends \TYPO3\CMS\Extbase\Tests\Unit\BaseTestCase {

	/**
	 * @test
	 *
	 * This test and the related Fixtures TxDomainModelTestEntity and
	 * TxDomainRepositoryTestEntityRepository can be removed if we do not need to support
	 * underscore class names instead of namespaced class names
	 */
	public function persistAllAddsReconstitutedObjectFromSessionToBackendsAggregateRootObjects() {
		eval ('
			class Foo_Bar_Domain_Model_BazFixture extends \\TYPO3\\CMS\\Extbase\\DomainObject\\AbstractEntity {}
		');
		eval ('
			class Foo_Bar_Domain_Repository_BazFixtureRepository {}
		');

		$persistenceSession = new \TYPO3\CMS\Extbase\Persistence\Generic\Session();
		$aggregateRootObjects = new \TYPO3\CMS\Extbase\Persistence\ObjectStorage();
		$entity1 = new \Foo_Bar_Domain_Model_BazFixture();
		$aggregateRootObjects->attach($entity1);
		$persistenceSession->registerReconstitutedObject($entity1);
		$mockTypo3DbBackend = $this->getMock($this->buildAccessibleProxy('TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Storage\\Typo3DbBackend'), array('commit','setAggregateRootObjects','setDeletedObjects'), array(), '', FALSE);
		$persistenceManager = $this->getMock($this->buildAccessibleProxy('TYPO3\\CMS\\Extbase\\Persistence\\Generic\\PersistenceManager'), array('dummy'),array(), '', FALSE);
		$mockTypo3DbBackend->expects($this->once())
						 ->method('setAggregateRootObjects')
						 ->with($this->equalTo($aggregateRootObjects));
		$persistenceManager->_set('backend',$mockTypo3DbBackend);
		$persistenceManager->injectSession($persistenceSession);
		$persistenceManager->persistAll();
	}

	/**
	 * @test
	 */
	public function persistAllAddsNamespacedReconstitutedObjectFromSessionToBackendsAggregateRootObjects() {
		eval ('
			namespace Foo\\Bar\\Domain\\Model;
			class BazFixture extends \\TYPO3\\CMS\\Extbase\\DomainObject\\AbstractEntity {}
		');
		eval ('
			namespace Foo\\Bar\\Domain\\Repository;
			class BazFixtureRepository {}
		');

		$persistenceSession = new \TYPO3\CMS\Extbase\Persistence\Generic\Session();
		$aggregateRootObjects = new \TYPO3\CMS\Extbase\Persistence\ObjectStorage();
		$entity1 = new \Foo\Bar\Domain\Model\BazFixture();
		$aggregateRootObjects->attach($entity1);
		$persistenceSession->registerReconstitutedObject($entity1);
		$mockTypo3DbBackend = $this->getMock($this->buildAccessibleProxy('TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Storage\\Typo3DbBackend'), array('commit','setAggregateRootObjects','setDeletedObjects'), array(), '', FALSE);
		$persistenceManager = $this->getMock($this->buildAccessibleProxy('TYPO3\\CMS\\Extbase\\Persistence\\Generic\\PersistenceManager'), array('dummy'),array(), '', FALSE);
		$mockTypo3DbBackend->expects($this->once())
						 ->method('setAggregateRootObjects')
						 ->with($this->equalTo($aggregateRootObjects));
		$persistenceManager->_set('backend',$mockTypo3DbBackend);
		$persistenceManager->injectSession($persistenceSession);
		$persistenceManager->persistAll();
	}

	/**
	 * @test
	 */
	public function persistAllAddsRemovedObjectsFromRepositoriesToBackendsDeletedObjects() {
		$this->markTestIncomplete();
	}

	/**
	 * @test
	 */
	public function persistAllAddsAddedObjectsFromRepositoriesToBackendsAggregateRootObjects() {
		$this->markTestIncomplete();
	}

}
?>