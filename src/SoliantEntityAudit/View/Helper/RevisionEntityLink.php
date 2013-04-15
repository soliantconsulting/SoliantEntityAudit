<?php

namespace SoliantEntityAudit\View\Helper;
use Zend\View\Helper\AbstractHelper;
use Doctrine\ORM\EntityManager;
use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Zend\View\Model\ViewModel;
use Zend\ServiceManager\ServiceLocatorInterface;

final class RevisionEntityLink extends AbstractHelper implements ServiceLocatorAwareInterface
{
    private $serviceLocator;

    public function setServiceLocator(ServiceLocatorInterface $serviceLocator) {
        $this->serviceLocator = $serviceLocator;
        return $this;
    }

    public function getServiceLocator() {
        return $this->serviceLocator;
    }

    public function __invoke($revisionEntity)
    {
        $view = $this->getServiceLocator()->getServiceLocator()->get('View');
        $model = new ViewModel();
        $model->setTemplate('soliant-entity-audit/helper/revision-entity-link.phtml');
        $model->setVariable('revisionEntity', $revisionEntity);
        $model->setOption('has_parent', true);
        return $view->render($model);
    }
}