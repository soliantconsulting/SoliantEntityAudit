<?php

namespace SoliantEntityAuditTest\Service;

use SoliantEntityAuditTest\Bootstrap
    , SoliantEntityAuditTest\Models\Album
    , Doctrine\Common\Persistence\Mapping\ClassMetadata
    ;

class LogRevisionTest extends \PHPUnit_Framework_TestCase
{

    // If we reach this function then the audit driver has worked
    public function testTrue()
    {
        $sm = Bootstrap::getApplication()->getServiceManager();
        $em = Bootstrap::getApplication()->getServiceManager()->get("doctrine.entitymanager.orm_default");
        $service = Bootstrap::getApplication()->getServiceManager()->get("auditService");

        $this->assertTrue(true);

    }
}