<?php

namespace SoliantEntityAuditTest\Options;

use SoliantEntityAudit\Options\ModuleOptions
    , SoliantEntityAudit\Tests\Util\ServiceManagerFactory
    , SoliantEntityAuditTest\Bootstrap
    ;

class ModuleOptionsTest extends \PHPUnit_Framework_TestCase
{
    protected $serviceManager;

    public function testModuleOptionDefaults()
    {
        $serviceManager = Bootstrap::getApplication()->getServiceManager();

        // For testing do not modify the di instance
        $moduleOptions = clone $serviceManager->get('auditModuleOptions');
        $moduleOptions->setDefaults(array());

        $this->assertEquals($moduleOptions->getJoinClasses(), array());
        $this->assertEquals($moduleOptions->getPaginatorLimit(), 20);
        $this->assertEquals($moduleOptions->getTableNamePrefix(), '');
        $this->assertEquals($moduleOptions->getTableNameSuffix(), '_audit');
        $this->assertEquals($moduleOptions->getRevisionTableName(), 'Revision');
        $this->assertEquals($moduleOptions->getRevisionEntityTableName(), 'RevisionEntity');
    }

    public function testModuleOptionsAuditedEntityClasses()
    {
        $serviceManager = Bootstrap::getApplication()->getServiceManager();

        // For testing do not modify the di instance
        $moduleOptions = clone $serviceManager->get('auditModuleOptions');
        $moduleOptions->setDefaults(array());

        $moduleOptions->setAuditedClassNames(array('Test1', 'Test2'));
        $this->assertEquals($moduleOptions->getAuditedClassNames(), array('Test1', 'Test2'));
    }
}
