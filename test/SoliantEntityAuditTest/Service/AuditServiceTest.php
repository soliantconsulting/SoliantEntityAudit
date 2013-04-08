<?php

namespace SoliantEntityAuditTest\Service;

use SoliantEntityAuditTest\Bootstrap
    , SoliantEntityAuditTest\Models\Bootstrap\Album
    , Doctrine\Common\Persistence\Mapping\ClassMetadata
    ;

class AuditServiceTest extends \PHPUnit_Framework_TestCase
{

    // If we reach this function then the audit driver has worked
    public function testCommentingAndCommentRestting()
    {
        $em = \SoliantEntityAudit\Module::getModuleOptions()->getEntityManager();
        $service = \SoliantEntityAudit\Module::getModuleOptions()->getAuditService();

        $service->setComment('Test comment is reset when read');
        $this->assertEquals('Test comment is reset when read', $service->getComment());
        $this->assertEquals(null, $service->getComment());
    }

    public function testRevisionComment()
    {
        // Inserting data insures we will have a result > 0
        $em = \SoliantEntityAudit\Module::getModuleOptions()->getEntityManager();
        $service = \SoliantEntityAudit\Module::getModuleOptions()->getAuditService();

        $entity = new Album;
        $entity->setTitle('Test 1');
        $entity->setArtist('Artist Test 1');

        $service->setComment('Test service comment is persisted on revision');
        $service = \SoliantEntityAudit\Module::getModuleOptions()->getAuditService();
        $this->assertEquals('Test service comment is persisted on revision', $service->getComment());

return;
        $em->persist($entity);
        $em->flush();

        $x = $em->getRepository('SoliantEntityAudit\\Entity\\Revision')->findAll();
        print_r($x);

        $em->persist($entity);
        $em->flush();

        $helper = Bootstrap::getApplication()->getServiceManager()->get('viewhelpermanager')->get('auditCurrentRevisionEntity');
        $lastEntityRevision = $helper($entity);

        print_r($lastEntityRevision->getRevision());die();

        $this->assertEquals('test 2', $lastEntityRevision->getRevision()->getComment());
    }

    public function testGetEntityValues() {
        // Inserting data insures we will have a result > 0
        $em = \SoliantEntityAudit\Module::getModuleOptions()->getEntityManager();
        $service = \SoliantEntityAudit\Module::getModuleOptions()->getAuditService();

        $service->setComment('test 2');

        $entity = new Album;
        $entity->setTitle('Test 1');
        $entity->setArtist('Artist Test 1');

        $this->assertEquals(array('artist' => 'Artist Test 1', 'title' => 'Test 1', 'id' => null), $service->getEntityValues($entity));
    }

    public function testGetRevisionEntities() {
        // Inserting data insures we will have a result > 0
        $em = \SoliantEntityAudit\Module::getModuleOptions()->getEntityManager();
        $service = \SoliantEntityAudit\Module::getModuleOptions()->getAuditService();

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
        $em = \SoliantEntityAudit\Module::getModuleOptions()->getEntityManager();
        $service = \SoliantEntityAudit\Module::getModuleOptions()->getAuditService();

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
        $em = \SoliantEntityAudit\Module::getModuleOptions()->getEntityManager();
        $service = \SoliantEntityAudit\Module::getModuleOptions()->getAuditService();

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
