<?php

namespace SoliantEntityAuditTest\View\Helper;

use SoliantEntityAuditTest\Bootstrap
    ;

class OptionsTest extends \PHPUnit_Framework_TestCase
{
    public function testOptionsEqualsConfig()
    {
        $sm = Bootstrap::getApplication()->getServiceManager();
        $helper = $sm->get('viewhelpermanager')->get('auditOptions');

        $sm = Bootstrap::getApplication()->getServiceManager()->get('auditModuleOptions');

        $this->assertEquals($helper(), $sm->getAuditedClassNames());
    }
}
