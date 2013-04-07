<?php

namespace SoliantEntityAuditTest\Loader;

use SoliantEntityAuditTest\Bootstrap
    , SoliantEntityAuditTest\Models\Album
    ;

class RevisionPaginatorTest extends \PHPUnit_Framework_TestCase
{
    private $key = 0;

    public function setUp()
    {
        // Inserting data insures we will have a result > 0
        $em = Bootstrap::getApplication()->getServiceManager()->get("doctrine.entitymanager.orm_default");

        $entity = new Album;
        $entity->setTitle('Test 1');
        $entity->setArtist('Artist Test 1');

        $em->persist($entity);
        $em->flush();

        $entity = new Album;
        $entity->setTitle('Change Test 2');
        $entity->setArtist('Change Artist Test 2');

        $em->persist($entity);
        $em->flush();
    }

    public function testRevisionsAreReturnedInPaginator()
    {
        $sm = Bootstrap::getApplication()->getServiceManager();
        $em = Bootstrap::getApplication()->getServiceManager()->get("doctrine.entitymanager.orm_default");

        $helper = $sm->get('viewhelpermanager')->get('auditRevisionPaginator');
        $count = sizeof($em->getRepository('SoliantEntityAudit\Entity\Revision')->findAll());

        $paginator = $helper($page = 0);
        $paginatedcount = 0;
        foreach ($paginator as $row)
            $paginatedcount ++;

        $this->assertGreaterThan(0, $count);
        $this->assertEquals($count, $paginatedcount);
    }

    public function tearDown() {
    }
}
