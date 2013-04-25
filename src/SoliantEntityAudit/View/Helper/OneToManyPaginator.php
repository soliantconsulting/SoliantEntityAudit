<?php

namespace SoliantEntityAudit\View\Helper;

use Zend\View\Helper\AbstractHelper;
use Doctrine\ORM\EntityManager;
use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\View\Model\ViewModel;
use DoctrineORMModule\Paginator\Adapter\DoctrinePaginator as DoctrineAdapter;
use Doctrine\ORM\Tools\Pagination\Paginator as ORMPaginator;
use Zend\Paginator\Paginator;
use SoliantEntityAudit\Entity\AbstractAudit;

final class OneToManyPaginator extends AbstractHelper implements ServiceLocatorAwareInterface
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

    public function __invoke($page, $revisionEntity, $joinTable, $mappedBy)
    {
        $auditModuleOptions = $this->getServiceLocator()->getServiceLocator()->get('auditModuleOptions');
        $entityManager = $auditModuleOptions->getEntityManager();
        $auditService = $this->getServiceLocator()->getServiceLocator()->get('auditService');

        $entityClassName = 'SoliantEntityAudit\\Entity\\' . str_replace('\\', '_', $joinTable);

        $query = $entityManager->createQuery("
            SELECT e
            FROM SoliantEntityAudit\Entity\RevisionEntity e
            JOIN e.revision r
            WHERE e.id IN (
                SELECT re.id
                FROM $entityClassName s
                JOIN s.revisionEntity re
                WHERE s.$mappedBy = :var
            )
            ORDER BY r.timestamp DESC
        ");
        $query->setParameter('var', $revisionEntity->getTargetEntity());

        $adapter = new DoctrineAdapter(new ORMPaginator($query));
        $paginator = new Paginator($adapter);
        $paginator->setDefaultItemCountPerPage($auditModuleOptions->getPaginatorLimit());

        $paginator->setCurrentPageNumber($page);

        return $paginator;
    }
}

