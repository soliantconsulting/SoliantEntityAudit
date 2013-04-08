<?php

namespace SoliantEntityAuditTest\Service;

use SoliantEntityAuditTest\Bootstrap
    , SoliantEntityAuditTest\Models\LogRevision\Album
    , Doctrine\Common\Persistence\Mapping\ClassMetadata
    , Doctrine\ORM\Tools\Setup
    , Doctrine\ORM\EntityManager
    , Doctrine\ORM\Mapping\Driver\StaticPHPDriver
    , Doctrine\ORM\Mapping\Driver\XmlDriver
    , Doctrine\ORM\Mapping\Driver\DriverChain
    , SoliantEntityAudit\Mapping\Driver\AuditDriver
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

        $isDevMode = true;

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
            'SoliantEntityAuditTest\Models\LogRevision\Song' => array(),
        ));

        $entityManager = EntityManager::create($conn, $config);
        $moduleOptions->setEntityManager($entityManager);
        $schemaTool = new SchemaTool($entityManager);

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

    public function tearDown()
    {
        // Replace entity manager
        $moduleOptions = \SoliantEntityAudit\Module::getModuleOptions();
        $moduleOptions->setEntityManager($this->_oldEntityManager);
        \SoliantEntityAudit\Module::getModuleOptions()->setAuditedClassNames($this->_oldAuditedClassNames);
    }
}