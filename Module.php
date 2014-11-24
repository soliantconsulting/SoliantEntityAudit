<?php

namespace SoliantEntityAudit;

use Zend\Mvc\MvcEvent
    , SoliantEntityAudit\Options\ModuleOptions
    , SoliantEntityAudit\Service\AuditService
    , SoliantEntityAudit\Loader\AuditAutoloader
    , SoliantEntityAudit\EventListener\LogRevision
    , SoliantEntityAudit\View\Helper\DateTimeFormatter
    , SoliantEntityAudit\View\Helper\EntityValues
    ;

class Module
{
    private static $moduleOptions;

    public function getAutoloaderConfig()
    {
        return array(
            'Zend\Loader\StandardAutoloader' => array(
                'namespaces' => array(
                    __NAMESPACE__ => __DIR__ . '/src/' . __NAMESPACE__,
                ),
            ),

            'SoliantEntityAudit\Loader\AuditAutoloader' => array(
                'namespaces' => array(
                    'SoliantEntityAudit\Entity' => __DIR__,
                )
            ),
        );
    }

    public function onBootstrap(MvcEvent $e)
    {
        $moduleOptions = $e->getApplication()->getServiceManager()->get('auditModuleOptions');

        self::setModuleOptions($moduleOptions);
    }

    public static function setModuleOptions(ModuleOptions $moduleOptions)
    {
        self::$moduleOptions = $moduleOptions;
    }

    public static function getModuleOptions()
    {
        return self::$moduleOptions;
    }

    public function getConfig()
    {
        return include __DIR__ . '/config/module.config.php';
    }

    public function getServiceConfig()
    {
        return array(
            'factories' => array(
                'auditModuleOptions' => function($serviceManager){
                    $config = $serviceManager->get('Application')->getConfig();
                    $auditConfig = new ModuleOptions();
                    $auditConfig->setDefaults($config['audit']);
                    $auditConfig->setEntityManager($serviceManager->get('doctrine.entitymanager.orm_default'));
                    $auditConfig->setAuditService($serviceManager->get('auditService'));

                    $auth = $serviceManager->get($auditConfig->getAuthenticationService());
                    if ($auth->hasIdentity()) {
                        if ($auditConfig->getEntityManager()->contains($auth->getIdentity())) {
                            $auditConfig->setUser($auth->getIdentity());
                        } else {
                            $auditConfig->setUser($auditConfig->getEntityManager()->merge($auth->getIdentity()));
                        }
                    }

                    return $auditConfig;
                },

                'auditService' => function($sm) {
                    return new AuditService();
                }
            ),
        );
    }

    public function getViewHelperConfig()
    {
         return array(
            'factories' => array(
                'auditDateTimeFormatter' => function($sm) {
                    $Servicelocator = $sm->getServiceLocator();
                    $config = $Servicelocator->get("Config");
                    $format = $config['audit']['datetimeFormat'];
                    $formatter = new DateTimeFormatter();
                    return $formatter->setDateTimeFormat($format);
                },

                'auditService' => function($sm) {
                    return new AuditService();
                }
            )
        );
    }
}
