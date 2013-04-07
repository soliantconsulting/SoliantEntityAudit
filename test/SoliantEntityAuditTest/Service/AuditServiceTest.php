<?php

namespace SoliantEntityAuditTest\Service;

use SoliantEntityAuditTest\Bootstrap
    , SoliantEntityAuditTest\Models\Album
    , Doctrine\Common\Persistence\Mapping\ClassMetadata
    ;

class AuditServiceTest extends \PHPUnit_Framework_TestCase
{

    // If we reach this function then the audit driver has worked
    public function testCommentingAndCommentRestting()
    {
        $sm = Bootstrap::getApplication()->getServiceManager();
        $em = Bootstrap::getApplication()->getServiceManager()->get("doctrine.entitymanager.orm_default");
        $service = Bootstrap::getApplication()->getServiceManager()->get("auditService");

        $service->setComment('test');
        $this->assertEquals('test', $service->getComment());
        $this->assertEquals(null, $service->getComment());
    }

    public function testRevisionCommentint()
    {
        // Inserting data insures we will have a result > 0
        $em = Bootstrap::getApplication()->getServiceManager()->get("doctrine.entitymanager.orm_default");

        $service = Bootstrap::getApplication()->getServiceManager()->get("auditService");

        $entity = new Album;
        $entity->setTitle('Test 1');
        $entity->setArtist('Artist Test 1');

        $service->setComment('test 1');

        $em->persist($entity);
        $em->flush();

        // ** FAIL **
        // This test is failing because primary keys are not autogenerating uniquely
        // in sqlite
        # $x = $em->getRepository('SoliantEntityAudit\\Entity\\Revision')->findAll();
        # print_r($x);


        $entity->setTitle('Test 1');
        $entity->setArtist('Artist Test 1');

        $service->setComment('test 2');

        $em->persist($entity);
        $em->flush();

        $helper = Bootstrap::getApplication()->getServiceManager()->get('viewhelpermanager')->get('auditCurrentRevisionEntity');
        $lastEntityRevision = $helper($entity);

        $this->assertEquals('test 2', $lastEntityRevision->getRevision()->getComment());
    }

    public function testGetEntityValues() {
        // Inserting data insures we will have a result > 0
        $em = Bootstrap::getApplication()->getServiceManager()->get("doctrine.entitymanager.orm_default");

        $service = Bootstrap::getApplication()->getServiceManager()->get("auditService");

        $service->setComment('test 2');

        $entity = new Album;
        $entity->setTitle('Test 1');
        $entity->setArtist('Artist Test 1');

        $this->assertEquals(array('artist' => 'Artist Test 1', 'title' => 'Test 1', 'id' => null), $service->getEntityValues($entity));
    }

    public function testGetRevisionEntities() {
        // Inserting data insures we will have a result > 0
        $em = Bootstrap::getApplication()->getServiceManager()->get("doctrine.entitymanager.orm_default");

        $service = Bootstrap::getApplication()->getServiceManager()->get("auditService");

        $service->setComment('test 2');

        $entity = new Album;
        $entity->setTitle('Test 1');
        $entity->setArtist('Artist Test 1');

        $em->persist($entity);
        $em->flush();

        $entity->setTitle('Test 2');
        $entity->setArtist('Artist Test 2');

        $em->flush();

        $this->assertEquals(2, sizeof($service->getRevisionEntities($entity)));
    }

    public function testGetRevisionEntitiesByRevisionEntity()
    {
        // Inserting data insures we will have a result > 0
        $em = Bootstrap::getApplication()->getServiceManager()->get("doctrine.entitymanager.orm_default");

        $service = Bootstrap::getApplication()->getServiceManager()->get("auditService");

        $service->setComment('test 2');

        $entity = new Album;
        $entity->setTitle('Test 1');
        $entity->setArtist('Artist Test 1');

        $em->persist($entity);
        $em->flush();

        $entity->setTitle('Test 2');
        $entity->setArtist('Artist Test 2');

        $em->flush();

        $serviceEntities = $service->getRevisionEntities($entity);

        $this->assertEquals(2, sizeof($service->getRevisionEntities(array_shift($serviceEntities)->getAuditEntity())));

    }

    public function testGetRevisionEntitiesByEntityClass()
    {
        // Inserting data insures we will have a result > 0
        $em = Bootstrap::getApplication()->getServiceManager()->get("doctrine.entitymanager.orm_default");

        $service = Bootstrap::getApplication()->getServiceManager()->get("auditService");

        $service->setComment('test 2');

        $entity = new Album;
        $entity->setTitle('Test 1');
        $entity->setArtist('Artist Test 1');

        $em->persist($entity);
        $em->flush();

        $entity->setTitle('Test 2');
        $entity->setArtist('Artist Test 2');

        $em->flush();

        $serviceEntities = $service->getRevisionEntities($entity);

        $this->assertGreaterThan(1, sizeof($service->getRevisionEntities(get_class($entity))));

    }

}
