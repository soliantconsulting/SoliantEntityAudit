<?php

namespace SoliantEntityAudit\Options;
use ZfcUser\Entity\UserInterface as ZfcUserInterface
    , Doctrine\ORM\EntityManager
    , SoliantEntityAudit\Service\AuditService
    ;

class ModuleOptions
{
    private $prefix;
    private $suffix;
    private $revisionTableName;
    private $revisionEntityTableName;
    private $auditedClassNames;
    private $joinClasses;
    private $user;
    private $entityManager;
    private $auditService;
    private $zfcUserEntityClassName;

    public function setDefaults(array $config)
    {
        $this->setPaginatorLimit(isset($config['tableNamePrefix']) ? $config['paginator.limit']: 20);
        $this->setTableNamePrefix(isset($config['tableNamePrefix']) ? $config['tableNamePrefix']: null);
        $this->setTableNameSuffix(isset($config['tableNameSuffix']) ? $config['tableNameSuffix']: '_audit');
        $this->setAuditedClassNames(isset($config['entities']) ? $config['entities']: array());
        $this->setRevisionTableName(isset($config['revisionTableName']) ? $config['revisionTableName']: 'Revision');
        $this->setRevisionEntityTableName(isset($config['revisionEntityTableName']) ? $config['revisionEntityTableName']: 'RevisionEntity');
    }

    public function getAuditService()
    {
        return $this->auditService;
    }

    public function setAuditService(AuditService $auditService)
    {
        $this->auditService = $auditService;
    }

    public function getEntityManager()
    {
        return $this->entityManager;
    }

    public function setEntityManager(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function getZfcUserEntityClassName()
    {
        return $this->zfcUserEntityClassName;
    }

    public function setZfcUserEntityClassName($className)
    {
        $this->zfcUserEntityClassName = $className;
    }

    public function addJoinClass($fullyQualifiedAuditClassName, $mapping)
    {
        $this->joinClasses[$fullyQualifiedAuditClassName] = $mapping;
        return $this;
    }

    public function getJoinClasses()
    {
        if (!$this->joinClasses) $this->joinClasses = array();
        return $this->joinClasses;
    }

    public function resetJoinClasses($joinClasses = array())
    {
        $oldClasses = $this->joinClasses;
        $this->joinClasses = $joinClasses;
        return $oldClasses;
    }

    public function getPaginatorLimit()
    {
        return $this->paginatorLimit;
    }

    public function setPaginatorLimit($rows)
    {
        $this->paginatorLimit = $rows;
        return $this;
    }

    public function getTableNamePrefix()
    {
        return $this->prefix;
    }

    public function setTableNamePrefix($prefix)
    {
        $this->prefix = $prefix;
        return $this;
    }

    public function getTableNameSuffix()
    {
        return $this->suffix;
    }

    public function setTableNameSuffix($suffix)
    {
        $this->suffix = $suffix;
        return $this;
    }

    public function getRevisionFieldName()
    {
        return 'revision';
    }

    public function getRevisionEntityFieldName()
    {
        return 'revisionEntity';
    }

    public function getRevisionTableName()
    {
        return $this->revisionTableName;
    }

    public function setRevisionTableName($revisionTableName)
    {
        $this->revisionTableName = $revisionTableName;
        return $this;
    }

    public function getRevisionEntityTableName()
    {
        return $this->revisionEntityTableName;
    }

    public function setRevisionEntityTableName($value)
    {
        $this->revisionEntityTableName = $value;
        return $this;
    }

    public function getAuditedClassNames()
    {
        if (!$this->auditedClassNames) $this->setAuditedClassNames(array());
        return $this->auditedClassNames;
    }

    public function setAuditedClassNames(array $classes)
    {
        $this->auditedClassNames = $classes;
        return $this;
    }

    public function setUser(ZfcUserInterface $user)
    {
        $this->user = $user;
        return $this;
    }

    public function getUser()
    {
        return $this->user;
    }
}
