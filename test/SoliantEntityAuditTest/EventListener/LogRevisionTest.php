<?php

namespace SoliantEntityAuditTest\Service;

use SoliantEntityAuditTest\Bootstrap
    , SoliantEntityAuditTest\Models\Album
    , Doctrine\Common\Persistence\Mapping\ClassMetadata
    , Doctrine\ORM\Tools\Setup
    , Doctrine\ORM\EntityManager
    , Doctrine\ORM\Mapping\Driver\StaticPHPDriver
    , SoliantEntityAudit\Mapping\Driver\AuditDriver
    ;

class LogRevisionTest extends \PHPUnit_Framework_TestCase
{
    private $_em;
    private $_oldEntityManager;

    public function setUp()
    {
        $isDevMode = true;

        $config = Setup::createConfiguration($isDevMode, null, null);
        $config->setMetadataDriverImpl(new StaticPHPDriver(array(__DIR__."/../Models")));
        $config->setMetadataDriverImpl(new AuditDriver());



        $conn = array(
            'driver' => 'pdo_sqlite',
            'memory' => true,
        );

        $entityManager = EntityManager::create($conn, $config);

        // Replace entity manager
        $this->_oldEntityManager = \SoliantEntityAudit\Module::getModuleOptions()->getEntityManager();
        $moduleOptions = \SoliantEntityAudit\Module::getModuleOptions();
        $moduleOptions->setEntityManager($entityManager);

        $schemaTool = new \Doctrine\ORM\Tools\SchemaTool($entityManager);
return;
die('ok');
        $sql = $schemaTool->getUpdateSchemaSql($entityManager->getMetadataFactory()->getAllMetadata());

        print_r($sql);die();

        $schemaTool->createSchema($entityManager->getMetadataFactory()->getAllMetadata());

die('ok');

        $this->_em = $entityManager;

        die('created');
    }

    // If we reach this function then the audit driver has worked
    public function testTrue()
    {
        $sm = Bootstrap::getApplication()->getServiceManager();
        $em = Bootstrap::getApplication()->getServiceManager()->get("doctrine.entitymanager.orm_default");
        $service = Bootstrap::getApplication()->getServiceManager()->get("auditService");

        $this->assertTrue(true);
    }

    public function tearDown()
    {
        // Replace entity manager
        $moduleOptions = \SoliantEntityAudit\Module::getModuleOptions();
        $moduleOptions->setEntityManager($this->_oldEntityManager);
    }
}