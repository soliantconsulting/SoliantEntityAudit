<?php

namespace SoliantEntityAuditTest\View\Helper;

use SoliantEntityAuditTest\Bootstrap
    , SoliantEntityAuditTest\Models\Bootstrap\Album
    ;

class RevisionEntityPaginatorTest extends \PHPUnit_Framework_TestCase
{
    private $entity;

    public function setUp()
    {
        // Inserting data insures we will have a result > 0
        $em = \SoliantEntityAudit\Module::getModuleOptions()->getEntityManager();

        $entity = new Album;
        $entity->setTitle('Test 1');

        $em->persist($entity);
        $em->flush();

        $entity->setTitle('Change Test 2');

        $em->flush();

        $this->entity = $entity;
    }

    public function testRevisionEntitiesAreReturnedInPaginator()
    {
        $sm = Bootstrap::getApplication()->getServiceManager();
        $em = \SoliantEntityAudit\Module::getModuleOptions()->getEntityManager();

        $helper = $sm->get('viewhelpermanager')->get('auditRevisionEntityPaginator');
        $revisionEntities = $em->getRepository('SoliantEntityAudit\Entity\RevisionEntity')->findBy(array(
            'targetEntityClass' => get_class($this->entity),
            'entityKeys' => serialize(array('id' => $this->entity->getId()))
        ));
        $count = sizeof($revisionEntities);

        $paginator = $helper($page = 0, $this->entity);
        $paginatedcount = 0;
        foreach ($paginator as $row)
            $paginatedcount ++;

        $this->assertGreaterThan(0, $count);
        $this->assertEquals($count, $paginatedcount);
    }

    public function testPaginatorCanAcceptAuditClass()
    {

        $sm = Bootstrap::getApplication()->getServiceManager();
        $em = \SoliantEntityAudit\Module::getModuleOptions()->getEntityManager();

        $helper = $sm->get('viewhelpermanager')->get('auditRevisionEntityPaginator');
        $revisionEntities = $em->getRepository('SoliantEntityAudit\Entity\RevisionEntity')->findBy(array(
            'targetEntityClass' => get_class($this->entity),
            'entityKeys' => serialize(array('id' => $this->entity->getId()))
        ));
        $count = sizeof($revisionEntities);

        $paginator = $helper($page = 0, array_shift($revisionEntities)->getAuditEntity());
        $paginatedcount = 0;
        foreach ($paginator as $row)
            $paginatedcount ++;

        $this->assertGreaterThan(0, $count);
        $this->assertEquals($count, $paginatedcount);

    }

    public function testPaginatorCanAcceptAuditedClassName()
    {

        $sm = Bootstrap::getApplication()->getServiceManager();
        $em = \SoliantEntityAudit\Module::getModuleOptions()->getEntityManager();

        $helper = $sm->get('viewhelpermanager')->get('auditRevisionEntityPaginator');
        $revisionEntities = $em->getRepository('SoliantEntityAudit\Entity\RevisionEntity')->findAll();

        $count = sizeof($revisionEntities);

        $paginator = $helper($page = 0, get_class(array_shift($revisionEntities)->getTargetEntity()));
        $paginatedcount = 0;
        foreach ($paginator as $row)
            $paginatedcount ++;

        $this->assertGreaterThan(0, $count);
        $this->assertEquals($count, $paginatedcount);

    }
}
