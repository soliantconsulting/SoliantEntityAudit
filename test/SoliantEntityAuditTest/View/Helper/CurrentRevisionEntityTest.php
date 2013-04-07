<?php

namespace SoliantEntityAuditTest\View\Helper;

use SoliantEntityAuditTest\Bootstrap
    , SoliantEntityAuditTest\Models\Album
    ;

class CurrentRevisionEntityTest extends \PHPUnit_Framework_TestCase
{
    private $entity;

    public function setUp()
    {
        // Inserting data insures we will have a result > 0
        $em = Bootstrap::getApplication()->getServiceManager()->get("doctrine.entitymanager.orm_default");

        $entity = new Album;
        $entity->setTitle('Test 1');
        $entity->setArtist('Artist Test 1');

        $em->persist($entity);
        $em->flush();

        $entity->setTitle('Change Test 2');
        $entity->setArtist('Change Artist Test 2');

        $em->flush();

        $this->entity = $entity;
    }

    public function testReturnsRevisionEntity()
    {
        $sm = Bootstrap::getApplication()->getServiceManager();
        $em = Bootstrap::getApplication()->getServiceManager()->get("doctrine.entitymanager.orm_default");

        $helper = $sm->get('viewhelpermanager')->get('auditCurrentRevisionEntity');

        $revisionEntity = $helper($this->entity);

        // Test getRevisionEntities on Revision
        $this->assertGreaterThan(0, sizeof($revisionEntity->getRevision()->getRevisionEntities()));

        $this->assertInstanceOf('SoliantEntityAudit\Entity\RevisionEntity', $revisionEntity);
    }

    public function testDoesNotReturnRevisionEntity()
    {
        $sm = Bootstrap::getApplication()->getServiceManager();
        $em = Bootstrap::getApplication()->getServiceManager()->get("doctrine.entitymanager.orm_default");

        $helper = $sm->get('viewhelpermanager')->get('auditCurrentRevisionEntity');

        $entity = new Album();

        $revisionEntity = $helper($entity);

        $this->assertEquals(null, $revisionEntity);

    }
}
