<?php

namespace SoliantEntityAuditTest\View\Helper;

use SoliantEntityAuditTest\Bootstrap
    ;

class AuditServiceTest extends \PHPUnit_Framework_TestCase
{
    public function testFormatter()
    {
        $sm = Bootstrap::getApplication()->getServiceManager();
        $helper = $sm->get('viewhelpermanager')->get('auditService');

        $this->assertInstanceOf('SoliantEntityAudit\Service\AuditService', $helper);
    }
}
