<?php

namespace SoliantEntityAuditTest\Loader;

use SoliantEntityAuditTest\Bootstrap
    , SoliantEntityAudit\Controller\IndexController
    , Zend\Mvc\Router\Http\TreeRouteStack as HttpRouter
    , Zend\Http\Request
    , Zend\Http\Response
    , Zend\Mvc\MvcEvent
    , Zend\Mvc\Router\RouteMatch
    , PHPUnit_Framework_TestCase
    , Doctrine\ORM\Query\ResultSetMapping
    , Doctrine\ORM\Query\ResultSetMappingBuilder
    , Doctrine\ORM\Mapping\ClassMetadata
    , Doctrine\ORM\Mapping\Driver\StaticPhpDriver
    , Doctrine\ORM\Mapping\Driver\PhpDriver
    , SoliantEntityAudit\Options\ModuleOptions
    , SoliantEntityAudit\Service\AuditService
    , SoliantEntityAudit\Loader\AuditAutoloader
    , SoliantEntityAudit\EventListener\LogRevision
    , SoliantEntityAudit\View\Helper\DateTimeFormatter
    , SoliantEntityAudit\View\Helper\EntityValues
    , Zend\ServiceManager\ServiceManager

    ;

class AuditAutoloaderTest extends \PHPUnit_Framework_TestCase
{
    protected $serviceManager;

    protected function setUp()
    {
        $this->_sm = Bootstrap::getApplication()->getServiceManager();
        $this->_em = $this->_sm->get('doctrine.entitymanager.orm_default');
        $this->_schemaTool = new \Doctrine\ORM\Tools\SchemaTool($this->_em);
    }

    public function testAutoloaderCanLoadAuditEntities()
    {
        $options = $this->_sm->get('auditModuleOptions');
        foreach ($options->getAuditedEntityClasses() as $className => $route) {
            $auditClassName = 'SoliantEntityAudit\\Entity\\' . str_replace('\\', '_', $className);
            $this->assertInstanceOf('SoliantEntityAudit\\Entity\\AbstractAudit', new $auditClassName());
        }
    }
}
