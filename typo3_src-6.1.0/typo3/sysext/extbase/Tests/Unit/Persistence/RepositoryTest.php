<?php
namespace TYPO3\CMS\Extbase\Tests\Unit\Persistence;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010 Bastian Waidelich <bastian@typo3.org>
 *  All rights reserved
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
class RepositoryTest extends \TYPO3\CMS\Extbase\Tests\Unit\BaseTestCase {

	/**
	 * @var \TYPO3\CMS\Extbase\Persistence\Repository|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface
	 */
	protected $repository;

	/**
	 * @var \TYPO3\CMS\Extbase\Object\ObjectManagerInterface
	 */
	protected $mockObjectManager;

	/**
	 * @var \TYPO3\CMS\Extbase\Persistence\Generic\IdentityMap
	 */
	protected $mockIdentityMap;

	/**
	 * @var \TYPO3\CMS\Extbase\Persistence\Generic\Session
	 */
	protected $mockSession;

	/**
	 * @var \TYPO3\CMS\Extbase\Persistence\Generic\QueryFactory
	 */
	protected $mockQueryFactory;

	/**
	 * @var \TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface
	 */
	protected $mockPersistenceManager;

	/**
	 * @var \TYPO3\CMS\Extbase\Persistence\QueryInterface
	 */
	protected $mockQuery;

	/**
	 * @var \TYPO3\CMS\Extbase\Persistence\Generic\BackendInterface
	 */
	protected $mockBackend;

	/**
	 * @var \TYPO3\CMS\Extbase\Persistence\Generic\QuerySettingsInterface
	 */
	protected $mockQuerySettings;

	public function setUp() {
		$this->mockSession = $this->getMock('TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Session');
		$this->mockIdentityMap = $this->getMock('TYPO3\\CMS\\Extbase\\Persistence\\Generic\\IdentityMap');
		$this->mockQuery = $this->getMock('TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Query', array('equals', 'matching', 'execute', 'comparison', 'setLimit'), array(), '', FALSE);
		$this->mockQuerySettings = $this->getMock('TYPO3\\CMS\\Extbase\\Persistence\\Generic\\QuerySettingsInterface');
		$this->mockQuery->expects($this->any())->method('getQuerySettings')->will($this->returnValue($this->mockQuerySettings));
		$this->mockQueryFactory = $this->getMock('TYPO3\\CMS\\Extbase\\Persistence\\Generic\\QueryFactory');
		$this->mockQueryFactory->expects($this->any())->method('create')->will($this->returnValue($this->mockQuery));

		$configuration = $this->getMock('\TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface');
		$this->mockBackend = $this->getMock('TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Backend', array('getIdentifierByObject', 'replaceObject'), array($configuration));
		$this->mockBackend->injectIdentityMap($this->mockIdentityMap);
		$this->mockBackend->injectQueryFactory($this->mockQueryFactory);
		$this->mockBackend->expects($this->any())->method('replaceObject');

		$this->mockPersistenceManager = $this->getAccessibleMock('TYPO3\\CMS\\Extbase\\Persistence\\Generic\\PersistenceManager', array('getIdentifierByObject'));
		$this->mockPersistenceManager->_set('addedObjects', new \TYPO3\CMS\Extbase\Persistence\ObjectStorage);
		$this->mockPersistenceManager->_set('removedObjects', new \TYPO3\CMS\Extbase\Persistence\ObjectStorage);
		$this->mockPersistenceManager->injectQueryFactory($this->mockQueryFactory);
		$this->mockPersistenceManager->injectBackend($this->mockBackend);
		$this->mockPersistenceManager->injectSession($this->mockSession);
		$this->mockPersistenceManager->setDefaultQuerySettings($this->mockQuerySettings);

		$this->mockObjectManager = $this->getMock('TYPO3\\CMS\\Extbase\\Object\\ObjectManagerInterface');
		$this->repository = $this->getAccessibleMock('TYPO3\\CMS\\Extbase\\Persistence\\Repository', array('dummy'), array($this->mockObjectManager));
		$this->repository->injectPersistenceManager($this->mockPersistenceManager);
	}

	/**
	 * @test
	 */
	public function abstractRepositoryImplementsRepositoryInterface() {
		$this->assertTrue($this->repository instanceof \TYPO3\CMS\Extbase\Persistence\RepositoryInterface);
	}

	/**
	 * @test
	 */
	public function addActuallyAddsAnObjectToTheInternalObjectsArray() {
		$someObject = new \stdClass();
		$this->repository->_set('entityClassName', get_class($someObject));
		$this->repository->add($someObject);
		$this->assertTrue($this->repository->getAddedObjects()->contains($someObject));
	}

	/**
	 * @test
	 */
	public function removeActuallyRemovesAnObjectFromTheInternalObjectsArray() {
		$object1 = $this->getMock('TYPO3\\CMS\\Extbase\\DomainObject\\AbstractDomainObject');
		$object2 = $this->getMock('TYPO3\\CMS\\Extbase\\DomainObject\\AbstractDomainObject');
		$object3 = $this->getMock('TYPO3\\CMS\\Extbase\\DomainObject\\AbstractDomainObject');
		$this->repository->_set('entityClassName', get_class($object1));
		$this->repository->add($object1);
		$this->repository->add($object2);
		$this->repository->add($object3);
		$this->repository->remove($object2);
		$this->assertTrue($this->repository->getAddedObjects()->contains($object1));
		$this->assertFalse($this->repository->getAddedObjects()->contains($object2));
		$this->assertTrue($this->repository->getAddedObjects()->contains($object3));
	}

	/**
	 * @test
	 */
	public function removeRemovesTheRightObjectEvenIfItHasBeenModifiedSinceItsAddition() {
		$object1 = $this->getMock('TYPO3\\CMS\\Extbase\\DomainObject\\AbstractDomainObject');
		$object2 = $this->getMock('TYPO3\\CMS\\Extbase\\DomainObject\\AbstractDomainObject');
		$object3 = $this->getMock('TYPO3\\CMS\\Extbase\\DomainObject\\AbstractDomainObject');
		$this->repository->_set('entityClassName', get_class($object1));
		$this->repository->add($object1);
		$this->repository->add($object2);
		$this->repository->add($object3);
		$object2->setPid(1);
		$object3->setPid(2);
		$this->repository->remove($object2);
		$this->assertTrue($this->repository->getAddedObjects()->contains($object1));
		$this->assertFalse($this->repository->getAddedObjects()->contains($object2));
		$this->assertTrue($this->repository->getAddedObjects()->contains($object3));
	}

	/**
	 * Make sure we remember the objects that are not currently add()ed
	 * but might be in persistent storage.
	 *
	 * @test
	 */
	public function removeRetainsObjectForObjectsNotInCurrentSession() {
		$object = $this->getMock('TYPO3\\CMS\\Extbase\\DomainObject\\AbstractDomainObject');
		// if the object is not currently add()ed, it is not new
		$object->expects($this->once())->method('_isNew')->will($this->returnValue(FALSE));
		$this->repository->_set('entityClassName', get_class($object));
		$this->repository->remove($object);
		$this->assertTrue($this->repository->getRemovedObjects()->contains($object));
	}

	/**
	 * dataProvider for createQueryCallsQueryFactoryWithExpectedType
	 *
	 * @return array
	 */
	public function modelAndRepositoryClassNames() {
		return array(
			array('Tx_BlogExample_Domain_Repository_BlogRepository', 'Tx_BlogExample_Domain_Model_Blog'),
			array('﻿_Domain_Repository_Content_PageRepository', '﻿_Domain_Model_Content_Page'),
			array('Tx_RepositoryExample_Domain_Repository_SomeModelRepository', 'Tx_RepositoryExample_Domain_Model_SomeModel'),
			array('Tx_RepositoryExample_Domain_Repository_RepositoryRepository', 'Tx_RepositoryExample_Domain_Model_Repository'),
			array('Tx_Repository_Domain_Repository_RepositoryRepository', 'Tx_Repository_Domain_Model_Repository')
		);
	}

	/**
	 * @test
	 * @dataProvider modelAndRepositoryClassNames
	 * @param string $repositoryClassName
	 * @param string $modelClassName
	 */
	public function constructSetsObjectTypeFromClassName($repositoryClassName, $modelClassName) {
		$mockClassName = 'MockRepository' . uniqid();
		eval('class ' . $repositoryClassName . ' extends TYPO3\\CMS\\Extbase\\Persistence\\Repository {}');
		$this->repository = new $repositoryClassName($this->mockObjectManager);
		$this->repository->injectPersistenceManager($this->mockPersistenceManager);
		$this->assertEquals($modelClassName, $this->repository->getEntityClassName());
	}

	/**
	 * dataProvider for createQueryCallsQueryFactoryWithExpectedType
	 *
	 * @return array
	 */
	public function modelAndRepositoryNamespacedClassNames() {
		return array(
			array('VENDOR\\EXT\\Domain\\Repository', 'BlogRepository', 'VENDOR\\EXT\\Domain\\Model\\Blog'),
			array('VENDOR\\EXT\\Domain\\Repository', '_PageRepository', 'VENDOR\\EXT\\Domain\\Model\\_Page'),
			array('VENDOR\\Repository\\Domain\\Repository', 'SomeModelRepository', 'VENDOR\\Repository\\Domain\\Model\\SomeModel'),
			array('VENDOR\\EXT\\Domain\\Repository', 'RepositoryRepository', 'VENDOR\\EXT\\Domain\\Model\\Repository'),
			array('VENDOR\\Repository\\Domain\\Repository', 'RepositoryRepository', 'VENDOR\\Repository\\Domain\\Model\\Repository'),
		);
	}

	/**
	 * @test
	 * @dataProvider modelAndRepositoryNamespacedClassNames
	 * @param string $namespace
	 * @param string $repositoryClassName
	 * @param string $modelClassName
	 */
	public function constructSetsObjectTypeFromNamespacedClassName($namespace, $repositoryClassName, $modelClassName) {
		$mockClassName = 'MockRepository' . uniqid();
		eval('namespace ' . $namespace . ';  class ' . $repositoryClassName . ' extends \\TYPO3\\CMS\\Extbase\\Persistence\\Repository {}');
		$namespacedRepositoryClassName = '\\' . $namespace . '\\' . $repositoryClassName;
		$this->repository = new $namespacedRepositoryClassName($this->mockObjectManager);
		$this->repository->injectPersistenceManager($this->mockPersistenceManager);
		$this->assertEquals($modelClassName, $this->repository->getEntityClassName());
	}

	/**
	 * @test
	 */
	public function createQueryCallsQueryFactoryWithExpectedClassName() {
		$this->mockQueryFactory->expects($this->once())->method('create')->with('ExpectedType');
		$this->repository->_set('entityClassName', 'ExpectedType');
		$this->repository->createQuery();
	}

	/**
	 * @test
	 */
	public function createQueryReturnsQueryWithUnmodifiedDefaultQuerySettings() {
		$mockQuerySettings = $this->getMock('TYPO3\\CMS\\Extbase\\Persistence\\Generic\\QuerySettingsInterface');
		$mockQuery = new \TYPO3\CMS\Extbase\Persistence\Generic\Query('foo');
		$mockQuery->setQuerySettings($mockQuerySettings);
		$this->repository->createQuery();
		$instanceQuerySettings = $mockQuery->getQuerySettings();
		$this->assertEquals($this->mockQuerySettings, $instanceQuerySettings);
		$this->assertNotSame($this->mockQuerySettings, $instanceQuerySettings);
	}

	/**
	 * @test
	 */
	public function findAllCreatesQueryAndReturnsResultOfExecuteCall() {
		$expectedResult = $this->getMock('TYPO3\\CMS\\Extbase\\Persistence\\QueryResultInterface');
		$this->mockQuery->expects($this->once())->method('execute')->with()->will($this->returnValue($expectedResult));
		$this->assertSame($expectedResult, $this->repository->findAll());
	}

	/**
	 * @test
	 */
	public function findByUidReturnsResultOfGetObjectByIdentifierCall() {
		$fakeUid = '123';
		$object = new \stdClass();
		$this->repository->_set('entityClassName', 'someObjectType');
		$this->mockIdentityMap->expects($this->once())->method('hasIdentifier')->with($fakeUid, 'someObjectType')->will($this->returnValue(TRUE));
		$this->mockIdentityMap->expects($this->once())->method('getObjectByIdentifier')->with($fakeUid)->will($this->returnValue($object));
		$expectedResult = $object;
		$actualResult = $this->repository->findByUid($fakeUid);
		$this->assertSame($expectedResult, $actualResult);
	}

	/**
	 * Replacing a reconstituted object (which has a uuid) by a new object
	 * will ask the persistence backend to replace them accordingly in the
	 * identity map.
	 *
	 * @test
	 * @return void
	 */
	public function replaceReplacesReconstitutedEntityByNewObject() {
		$existingObject = $this->getMock('TYPO3\\CMS\\Extbase\\DomainObject\\DomainObjectInterface');
		$newObject = $this->getMock('TYPO3\\CMS\\Extbase\\DomainObject\\DomainObjectInterface');

		$this->mockPersistenceManager->expects($this->any())->method('getIdentifierByObject')->will($this->returnValue('123'));
		$this->mockBackend->expects($this->any())->method('getIdentifierByObject')->will($this->returnValue('123'));

		$this->repository->_set('entityClassName', get_class($newObject));
		$this->repository->replace($existingObject, $newObject);
	}

	/**
	 * Replacing a reconstituted object which during this session has been
	 * marked for removal (by calling the repository's remove method)
	 * additionally registers the "newObject" for removal and removes the
	 * "existingObject" from the list of removed objects.
	 *
	 * @test
	 * @return void
	 */
	public function replaceRemovesReconstitutedObjectWhichIsMarkedToBeRemoved() {
		$existingObject = $this->getMock('TYPO3\\CMS\\Extbase\\DomainObject\\DomainObjectInterface');
		$newObject = $this->getMock('TYPO3\\CMS\\Extbase\\DomainObject\\DomainObjectInterface');

		$removedObjects = new \TYPO3\CMS\Extbase\Persistence\ObjectStorage();
		$removedObjects->attach($existingObject);

		$this->mockPersistenceManager->expects($this->any())->method('getIdentifierByObject')->will($this->returnValue('123'));
		$this->mockBackend->expects($this->any())->method('getIdentifierByObject')->will($this->returnValue('123'));

		$this->repository->_set('entityClassName', get_class($newObject));
		$this->repository->_get('persistenceManager')->_set('removedObjects', $removedObjects);
		$this->repository->replace($existingObject, $newObject);

		$this->assertFalse($this->repository->getRemovedObjects()->contains($existingObject));
		$this->assertTrue($this->repository->getRemovedObjects()->contains($newObject));
	}

	/**
	 * Replacing a new object which has not yet been persisted by another
	 * new object will just replace them in the repository's list of added
	 * objects.
	 *
	 * @test
	 * @return void
	 */
	public function replaceAddsNewObjectToAddedObjects() {
		$existingObject = $this->getMock('TYPO3\\CMS\\Extbase\\DomainObject\\DomainObjectInterface');
		$newObject = $this->getMock('TYPO3\\CMS\\Extbase\\DomainObject\\DomainObjectInterface');

		$addedObjects = new \TYPO3\CMS\Extbase\Persistence\ObjectStorage();
		$addedObjects->attach($existingObject);

		$this->repository->_set('entityClassName', get_class($newObject));
		$this->repository->_get('persistenceManager')->_set('addedObjects', $addedObjects);
		$this->repository->replace($existingObject, $newObject);

		$this->assertFalse($addedObjects->contains($existingObject));
		$this->assertTrue($addedObjects->contains($newObject));
	}

	/**
	 * @test
	 * @expectedException \TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException
	 */
	public function replaceChecksObjectType() {
		$this->repository->_set('entityClassName', 'ExpectedObjectType');
		$this->repository->replace(new \stdClass(), new \stdClass());
	}

	/**
	 * @test
	 */
	public function updateReplacesAnObjectWithTheSameUuidByTheGivenObject() {
		$existingObject = new \stdClass();
		$modifiedObject = $this->getMock('TYPO3\\CMS\\Extbase\\DomainObject\\DomainObjectInterface');
		$modifiedObject->expects($this->once())->method('getUid')->will($this->returnValue('123'));

		$mockPersistenceManager = $this->getAccessibleMock('TYPO3\\CMS\\Extbase\\Persistence\\Generic\\PersistenceManager', array('getObjectByIdentifier', 'findByUid', 'replace'));
		$mockPersistenceManager->_set('addedObjects', new \TYPO3\CMS\Extbase\Persistence\ObjectStorage);
		$mockPersistenceManager->_set('removedObjects', new \TYPO3\CMS\Extbase\Persistence\ObjectStorage);
		$mockPersistenceManager->injectBackend($this->mockBackend);
		$mockPersistenceManager->injectSession($this->mockSession);
		$mockPersistenceManager->expects($this->once())->method('getObjectByIdentifier')->with('123')->will($this->returnValue($existingObject));
		$mockPersistenceManager->expects($this->once())->method('replace')->with($existingObject, $modifiedObject);

		$this->repository->injectPersistenceManager($mockPersistenceManager);
		$this->repository->_set('entityClassName', get_class($modifiedObject));
		$this->repository->update($modifiedObject);
	}

	/**
	 * @test
	 * @expectedException \TYPO3\CMS\Extbase\Persistence\Exception\UnknownObjectException
	 */
	public function updateRejectsUnknownObjects() {
		$someObject = $this->getMock('TYPO3\\CMS\\Extbase\\DomainObject\\DomainObjectInterface');
		$someObject->expects($this->once())->method('getUid')->will($this->returnValue(NULL));
		$this->repository->_set('entityClassName', get_class($someObject));
		$this->repository->update($someObject);
	}

	/**
	 * @test
	 * @expectedException \TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException
	 */
	public function updateRejectsObjectsOfWrongType() {
		$this->repository->_set('entityClassName', 'Foo');
		$this->repository->update(new \stdClass());
	}

	/**
	 * @test
	 */
	public function magicCallMethodAcceptsFindBySomethingCallsAndExecutesAQueryWithThatCriteria() {
		$mockQueryResult = $this->getMock('TYPO3\\CMS\\Extbase\\Persistence\\QueryResultInterface');
		$this->mockQuery->expects($this->once())->method('equals')->with('foo', 'bar')->will($this->returnValue('matchCriteria'));
		$this->mockQuery->expects($this->once())->method('matching')->with('matchCriteria')->will($this->returnValue($this->mockQuery));
		$this->mockQuery->expects($this->once())->method('execute')->will($this->returnValue($mockQueryResult));
		$this->assertSame($mockQueryResult, $this->repository->findByFoo('bar'));
	}

	/**
	 * @test
	 */
	public function magicCallMethodAcceptsFindOneBySomethingCallsAndExecutesAQueryWithThatCriteria() {
		$object = new \stdClass();
		$mockQueryResult = $this->getMock('TYPO3\\CMS\\Extbase\\Persistence\\QueryResultInterface');
		$mockQueryResult->expects($this->once())->method('getFirst')->will($this->returnValue($object));
		$this->mockQuery->expects($this->once())->method('equals')->with('foo', 'bar')->will($this->returnValue('matchCriteria'));
		$this->mockQuery->expects($this->once())->method('matching')->with('matchCriteria')->will($this->returnValue($this->mockQuery));
		$this->mockQuery->expects($this->any())->method('setLimit')->with(1)->will($this->returnValue($this->mockQuery));
		$this->mockQuery->expects($this->once())->method('execute')->will($this->returnValue($mockQueryResult));
		$this->assertSame($object, $this->repository->findOneByFoo('bar'));
	}

	/**
	 * @test
	 */
	public function magicCallMethodAcceptsCountBySomethingCallsAndExecutesAQueryWithThatCriteria() {
		$mockQueryResult = $this->getMock('TYPO3\\CMS\\Extbase\\Persistence\\QueryResultInterface');
		$mockQueryResult->expects($this->once())->method('count')->will($this->returnValue(2));
		$this->mockQuery->expects($this->once())->method('equals')->with('foo', 'bar')->will($this->returnValue('matchCriteria'));
		$this->mockQuery->expects($this->once())->method('matching')->with('matchCriteria')->will($this->returnValue($this->mockQuery));
		$this->mockQuery->expects($this->once())->method('execute')->will($this->returnValue($mockQueryResult));
		$this->assertSame(2, $this->repository->countByFoo('bar'));
	}

	/**
	 * @test
	 * @expectedException \TYPO3\CMS\Extbase\Persistence\Generic\Exception\UnsupportedMethodException
	 */
	public function magicCallMethodTriggersAnErrorIfUnknownMethodsAreCalled() {
		$this->repository->__call('foo', array());
	}

	/**
	 * @test
	 * @expectedException \TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException
	 */
	public function addChecksObjectType() {
		$this->repository->_set('entityClassName', 'ExpectedObjectType');
		$this->repository->add(new \stdClass());
	}

	/**
	 * @test
	 * @expectedException \TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException
	 */
	public function removeChecksObjectType() {
		$this->repository->_set('entityClassName', 'ExpectedObjectType');
		$this->repository->remove(new \stdClass());
	}
}

?>