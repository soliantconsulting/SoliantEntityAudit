<?php

namespace SoliantEntityAuditTest\Controller;

use SoliantEntityAuditTest\Bootstrap
    , SoliantEntityAudit\Controller\IndexController
    , Zend\Mvc\Router\Http\TreeRouteStack as HttpRouter
    , Zend\Http\Request
    , Zend\Http\Response
    , Zend\Mvc\MvcEvent
    , Zend\Mvc\Router\RouteMatch
    , SoliantEntityAuditTest\Models\Bootstrap\Album
    ;

class IndexControllerTest extends \PHPUnit_Framework_TestCase
{
    protected $controller;
    protected $request;
    protected $response;
    protected $routeMatch;
    protected $event;

    protected function setUp()
    {
        $serviceManager = Bootstrap::getApplication()->getServiceManager();
        $this->controller = new IndexController();
        $this->request    = new Request();
        $this->routeMatch = new RouteMatch(array('controller' => 'index'));
        $this->event      = new MvcEvent();
        $config = $serviceManager->get('Config');
        $routerConfig = isset($config['router']) ? $config['router'] : array();
        $router = HttpRouter::factory($routerConfig);

        $this->event->setRouter($router);
        $this->event->setRouteMatch($this->routeMatch);
        $this->controller->setEvent($this->event);
        $this->controller->setServiceLocator($serviceManager);
    }

    public function testIndexActionCanBeAccessed()
    {
        $this->routeMatch->setParam('action', 'index');
        $this->routeMatch->setParam('controller', 'audit');

        $result   = $this->controller->dispatch($this->request);
        $response = $this->controller->getResponse();

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testUserActionCanBeAccessed()
    {
        $this->routeMatch->setParam('action', 'user');
        $this->routeMatch->setParam('controller', 'audit');
        $this->routeMatch->setParam('userId', 1);
        $this->routeMatch->setParam('page', 0);

        $result   = $this->controller->dispatch($this->request);
        $response = $this->controller->getResponse();

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testRevisionActionCanBeAccessed()
    {
        $em = Bootstrap::getApplication()->getServiceManager()->get("doctrine.entitymanager.orm_default");
        $sm = Bootstrap::getApplication()->getServiceManager();

        $entity = new Album;
        $entity->setTitle('test 1');

        $em->persist($entity);
        $em->flush();

        $helper = $sm->get('viewhelpermanager')->get('auditCurrentRevisionEntity');

        $revisionEntity = $helper($entity);

        $this->routeMatch->setParam('action', 'revision');
        $this->routeMatch->setParam('controller', 'audit');
        $this->routeMatch->setParam('revisionId', $revisionEntity->getRevision()->getId());

        $result   = $this->controller->dispatch($this->request);
        $response = $this->controller->getResponse();

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testRevisionEntityActionCanBeAccessed()
    {

        $em = Bootstrap::getApplication()->getServiceManager()->get("doctrine.entitymanager.orm_default");
        $sm = Bootstrap::getApplication()->getServiceManager();

        $entity = new Album;
        $entity->setTitle('test 1');

        $em->persist($entity);
        $em->flush();

        $helper = $sm->get('viewhelpermanager')->get('auditCurrentRevisionEntity');
        $revisionEntity = $helper($entity);

        $this->routeMatch->setParam('action', 'revision-entity');
        $this->routeMatch->setParam('controller', 'audit');
        $this->routeMatch->setParam('revisionEntityId', $revisionEntity->getId());

        $result   = $this->controller->dispatch($this->request);
        $response = $this->controller->getResponse();

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testEntityActionCanBeAccessed()
    {
        $this->routeMatch->setParam('action', 'entity');
        $this->routeMatch->setParam('controller', 'audit');
        $this->routeMatch->setParam('entity', 'SoliantEntityAuditTest\Models\Bootstrap\Album');
        $this->routeMatch->setParam('page', 0);

        $result   = $this->controller->dispatch($this->request);
        $response = $this->controller->getResponse();

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testCompareActionCanBeAccessed()
    {
        $this->routeMatch->setParam('action', 'compare');
        $this->routeMatch->setParam('controller', 'audit');
        $this->request->getPost()->set('revisionEntityId_old', 1);
        $this->request->getPost()->set('revisionEntityId_new', 2);

        $result   = $this->controller->dispatch($this->request);
        $response = $this->controller->getResponse();

        $this->assertEquals(200, $response->getStatusCode());
    }
}
