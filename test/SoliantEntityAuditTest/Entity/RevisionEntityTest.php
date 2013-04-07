<?php

namespace SoliantEntityAuditTest\Entity;

use SoliantEntityAuditTest\Bootstrap
    , SoliantEntityAudit\Entity\Revision
    , Doctrine\Common\Persistence\Mapping\ClassMetadata
    , SoliantEntityAuditTest\Models\Album
    ;

class RevisionEntityTest extends \PHPUnit_Framework_TestCase
{

    // If we reach this function then the audit driver has worked
    public function testGettersAndSetters()
    {        $em = Bootstrap::getApplication()->getServiceManager()->get("doctrine.entitymanager.orm_default");
        $sm = Bootstrap::getApplication()->getServiceManager();

        $entity = new Album;
        $entity->setArtist('artist test 1');
        $entity->setTitle('test 1');

        $em->persist($entity);
        $em->flush();

        $helper = $sm->get('viewhelpermanager')->get('auditCurrentRevisionEntity');

        $revisionEntity = $helper($entity);

        $this->assertEquals('INS', $revisionEntity->getRevisionType());
        $this->assertEquals($entity, $revisionEntity->getTargetEntity());
        $this->assertEquals('SoliantEntityAuditTest\Models\Album', $revisionEntity->getTargetEntityClass());

    }
}
