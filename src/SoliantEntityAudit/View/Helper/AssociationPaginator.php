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

final class AssociationPaginator extends AbstractHelper implements ServiceLocatorAwareInterface
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

    public function __invoke($page, $revisionEntity, $joinTable)
    {
        $auditModuleOptions = $this->getServiceLocator()->getServiceLocator()->get('auditModuleOptions');
        $entityManager = $auditModuleOptions->getEntityManager();
        $auditService = $this->getServiceLocator()->getServiceLocator()->get('auditService');

        foreach($auditService->getEntityAssociations($revisionEntity->getAuditEntity()) as $field => $value) {
            if (isset($value['joinTable']['name']) and $value['joinTable']['name'] == $joinTable) {
                $mapping = $value;
                break;
            }
        }

#print_r($mapping);die();

#$new = 'SoliantEntityAudit\\Entity\\' . str_replace('\\', '_', $joinTable);
#$x = new $new;

        $repository = $entityManager->getRepository('SoliantEntityAudit\\Entity\\' . str_replace('\\', '_', $joinTable));
/*
        $qb = $repository->createQueryBuilder('association');
        $qb->andWhere('association.revisionEntity = ?1');
        $qb->setParameter(1, $revisionEntity->getId());
#        $qb->orderBy('association.id', 'DESC');
*/
/*
        $i = 0;
        foreach($filter as $field => $value) {
            if (!is_null($value)) {
                $qb->andWhere("revision.$field = ?$i");
                $qb->setParameter($i, $value);
            } else {
                $qb->andWhere("revision.$field is NULL");
            }
        }
*/
        $adapter = new DoctrineAdapter(new ORMPaginator($qb));
        $paginator = new Paginator($adapter);
        $paginator->setDefaultItemCountPerPage($auditModuleOptions->getPaginatorLimit());

        $paginator->setCurrentPageNumber($page);

        return $paginator;
    }
}
