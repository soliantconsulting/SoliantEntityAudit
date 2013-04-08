<?php

namespace SoliantEntityAudit\View\Helper;

use Zend\View\Helper\AbstractHelper
    , Doctrine\ORM\EntityManager
    , Zend\ServiceManager\ServiceLocatorAwareInterface
    , Zend\ServiceManager\ServiceLocatorInterface
    , Zend\View\Model\ViewModel
    , DoctrineORMModule\Paginator\Adapter\DoctrinePaginator as DoctrineAdapter
    , Doctrine\ORM\Tools\Pagination\Paginator as ORMPaginator
    , Zend\Paginator\Paginator
    , SoliantEntityAudit\Entity\AbstractAudit
    ;

// Return the latest revision entity for the given entity
final class CurrentRevisionEntity extends AbstractHelper implements ServiceLocatorAwareInterface
{
    private $serviceLocator;

    public function getServiceLocator()
    {
        return $this->serviceLocator;
    }

    public function setServiceLocator(ServiceLocatorInterface $serviceLocator)
    {
        $this->serviceLocator = $serviceLocator;
        return $this;
    }

    public function __invoke($entity)
    {
        $entityManager = $this->getServiceLocator()->getServiceLocator()->get('auditModuleOptions')->getEntityManager();
        $auditService = $this->getServiceLocator()->getServiceLocator()->get('auditModuleOptions')->getAuditService();

        $revisionEntities = $entityManager->getRepository('SoliantEntityAudit\\Entity\\RevisionEntity')->findBy(array(
            'targetEntityClass' => get_class($entity),
            'entityKeys' => serialize($auditService->getEntityIdentifierValues($entity)),
        ), array('id' => 'DESC'), 1);

        if (sizeof($revisionEntities)) return $revisionEntities[0];
    }
}
