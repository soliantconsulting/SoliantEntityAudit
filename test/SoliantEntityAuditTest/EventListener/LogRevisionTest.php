<?php

namespace SoliantEntityAuditTest\Service;

use SoliantEntityAuditTest\Bootstrap
    , SoliantEntityAuditTest\Models\LogRevision\Album
    , SoliantEntityAuditTest\Models\LogRevision\Song
    , SoliantEntityAuditTest\Models\LogRevision\Performer
    , Doctrine\Common\Persistence\Mapping\ClassMetadata
    , Doctrine\ORM\Tools\Setup
    , Doctrine\ORM\EntityManager
    , Doctrine\ORM\Mapping\Driver\StaticPHPDriver
    , Doctrine\ORM\Mapping\Driver\XmlDriver
    , Doctrine\ORM\Mapping\Driver\DriverChain
    , SoliantEntityAudit\Mapping\Driver\AuditDriver
    , SoliantEntityAudit\EventListener\LogRevision
    , Doctrine\ORM\Tools\SchemaTool
    ;

class LogRevisionTest extends \PHPUnit_Framework_TestCase
{
    private $_em;
    private $_oldEntityManager;

    public function setUp()
    {
        $this->_oldEntityManager = \SoliantEntityAudit\Module::getModuleOptions()->getEntityManager();
        $this->_oldAuditedClassNames = \SoliantEntityAudit\Module::getModuleOptions()->getAuditedClassNames();
        $this->_oldJoinClasses = \SoliantEntityAudit\Module::getModuleOptions()->resetJoinClasses();

        $isDevMode = false;

        $config = Setup::createConfiguration($isDevMode, null, null);

        $chain = new DriverChain();
        // zfc user is required
        $chain->addDriver(new XmlDriver(__DIR__ . '/../../../vendor/zf-commons/zfc-user-doctrine-orm/config/xml/zfcuser')
            , 'ZfcUser\Entity');
        $chain->addDriver(new XmlDriver(__DIR__ . '/../../../vendor/zf-commons/zfc-user-doctrine-orm/config/xml/zfcuserdoctrineorm')
            , 'ZfcUserDoctrineORM\Entity');
        $chain->addDriver(new StaticPHPDriver(__DIR__ . "/../Models"), 'SoliantEntityAuditTest\Models\LogRevision');
        $chain->addDriver(new AuditDriver('.'), 'SoliantEntityAudit\Entity');

        $config->setMetadataDriverImpl($chain);

        // Replace entity manager
        $moduleOptions = \SoliantEntityAudit\Module::getModuleOptions();

        $conn = array(
            'driver' => 'pdo_sqlite',
            'memory' => true,
        );

        $moduleOptions->setAuditedClassNames(array(
            'SoliantEntityAuditTest\Models\LogRevision\Album' => array(),
            'SoliantEntityAuditTest\Models\LogRevision\Performer' => array(),
            'SoliantEntityAuditTest\Models\LogRevision\Song' => array(),
            'SoliantEntityAuditTest\Models\LogRevision\SingleCoverArt' => array(),
        ));

        $entityManager = EntityManager::create($conn, $config);
        $moduleOptions->setEntityManager($entityManager);
        $schemaTool = new SchemaTool($entityManager);

        // Add auditing listener
        $entityManager->getEventManager()->addEventSubscriber(new LogRevision());

        $sql = $schemaTool->getUpdateSchemaSql($entityManager->getMetadataFactory()->getAllMetadata());
        #print_r($sql);die();

        $schemaTool->createSchema($entityManager->getMetadataFactory()->getAllMetadata());

        $this->_em = $entityManager;

    }

    // If we reach this function then the audit driver has worked
    public function testAuditCreateUpdateDelete()
    {
        $album = new Album;
        $album->setTitle('Test entity lifecycle: CREATE');

        $this->_em->persist($album);
        $this->_em->flush();

        $album->setTitle('Test entity lifecycle: UPDATE');

        $this->_em->flush();

        $album->setTitle('Test entity lifecycle: DELETE');

        $this->_em->flush();


        $this->assertTrue(true);
    }

    public function testOneToManyAudit()
    {
        $album = new Album;
        $album->setTitle('Test One To Many Audit');

        $song = new Song;
        $song->setTitle('Test one to many audit song > album');

        $song->setAlbum($album);
        $album->getSongs()->add($song);

        $this->_em->persist($album);
        $this->_em->persist($song);

        $this->_em->flush();


        $persistedSong = $this->_em->getRepository('SoliantEntityAuditTest\Models\LogRevision\Song')->find($song->getId());

        $this->assertEquals($song, $persistedSong);
        $this->assertEquals($album, $persistedSong->getAlbum());
    }

    public function testManyToManyAudit()
    {
        $album = new Album;
        $album->setTitle('Test Many To Many Audit');

        $performer = new Performer;
        $performer->setName('Test many to many audit');

        $this->_em->persist($album);
        $this->_em->persist($performer);

        $this->_em->flush();

        $performer->getAlbums()->add($album);
        $album->getPerformers()->add($performer);

        $this->_em->flush();

        $moduleOptions = \SoliantEntityAudit\Module::getModuleOptions();
        $this->assertGreaterThan(0, sizeof($moduleOptions->getJoinClasses()));

        $manyToManys = $this->_em->getRepository('SoliantEntityAudit\Entity\performer_album')->findAll();
        $manyToMany = reset($manyToManys);

        $this->assertInstanceOf('SoliantEntityAudit\Entity\performer_album', $manyToMany);
        $manyToManyValues = $manyToMany->getArrayCopy();

        $this->assertEquals($album->getId(), $manyToManyValues['albums']);
        $this->assertEquals($performer->getId(), $manyToManyValues['performers']);
    }

    public function testAuditDeleteEntity()
    {
        $album = new Album;
        $album->setTitle('test audit delete entity');
        $this->_em->persist($album);

        $this->_em->flush();

        $this->_em->remove($album);
        $this->_em->flush();
    }

    public function testCollectionDeletion()
    {
        $album = new Album;
        $album->setTitle('Test collection deletion');

        $performer = new Performer;
        $performer->setName('Test collection deletion');

        $performer->getAlbums()->add($album);
        $album->getPerformers()->add($performer);

        $this->_em->flush();

        $performer->getAlbums()->removeElement($album);
        $album->getPerformers()->removeElement($performer);

        $this->_em->flush();

        $manyToManys = $this->_em->getRepository('SoliantEntityAudit\Entity\performer_album')->findAll();

        $this->assertEquals(array(), $manyToManys);

    }

    public function tearDown()
    {
        // Replace entity manager
        $moduleOptions = \SoliantEntityAudit\Module::getModuleOptions();
        $moduleOptions->setEntityManager($this->_oldEntityManager);
        \SoliantEntityAudit\Module::getModuleOptions()->setAuditedClassNames($this->_oldAuditedClassNames);
        \SoliantEntityAudit\Module::getModuleOptions()->resetJoinClasses($this->_oldJoinClasses);
    }
}