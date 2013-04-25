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

#select revisionEntity join $joinTable onetomany
#where onetomany.$mappedBy = $revisionEntity->getTargetEntity()->getId();

        $repository = $entityManager->getRepository('SoliantEntityAudit\\Entity\\' . str_replace('\\', '_', $joinTable));

        $qb = $repository->createQueryBuilder('onetomany');
        $qb->select('onetomany.revisionEntity');
#        $qb->select('onetomany.id');
#        $qb->innerJoin('onetomany.revisionEntity', 'revisionEntity');
#        $qb->andWhere('onetomany.' . $mappedBy . ' = :var');
#        $qb->setParameter('var', $revisionEntity->getTargetEntity()->getId());

print_r($qb->getQuery()->getDql());die();

        $adapter = new DoctrineAdapter(new ORMPaginator($qb));
        $paginator = new Paginator($adapter);
        $paginator->setDefaultItemCountPerPage($auditModuleOptions->getPaginatorLimit());

        $paginator->setCurrentPageNumber($page);

        return $paginator;
    }
}

