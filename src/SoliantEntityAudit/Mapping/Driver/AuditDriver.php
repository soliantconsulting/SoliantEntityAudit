<?php

namespace SoliantEntityAudit\Mapping\Driver;

use Doctrine\Common\Persistence\Mapping\ClassMetadata
    , Doctrine\Common\Persistence\Mapping\Driver\MappingDriver
    , Doctrine\ORM\Mapping\Builder\ClassMetadataBuilder
    , Doctrine\ORM\Mapping\MappingException
    ;

final class AuditDriver implements MappingDriver
{
    /**
     * Loads the metadata for the specified class into the provided container.
     *
     * @param string $className
     * @param ClassMetadata $metadata
     */
    function loadMetadataForClass($className, ClassMetadata $metadata)
    {
        $moduleOptions = \SoliantEntityAudit\Module::getModuleOptions();
        $entityManager = $moduleOptions->getEntityManager();
        $metadataFactory = $entityManager->getMetadataFactory();
        $builder = new ClassMetadataBuilder($metadata);

        if ($className == 'SoliantEntityAudit\\Entity\RevisionEntity') {
            $builder->createField('id', 'integer')->isPrimaryKey()->generatedValue()->build();
            $builder->addManyToOne('revision', 'SoliantEntityAudit\\Entity\\Revision', 'revisionEntities');
            $builder->addField('entityKeys', 'string');
            $builder->addField('auditEntityClass', 'string');
            $builder->addField('targetEntityClass', 'string');
            $builder->addField('revisionType', 'string');
            $builder->addField('title', 'string', array('nullable' => true));

            $metadata->setTableName($moduleOptions->getRevisionEntityTableName());
            return;
        }

        // Revision is managed here rather than a separate namespace and driver
        if ($className == 'SoliantEntityAudit\\Entity\\Revision') {
            $builder->createField('id', 'integer')->isPrimaryKey()->generatedValue()->build();
            $builder->addField('comment', 'text', array('nullable' => true));
            $builder->addField('timestamp', 'datetime');

            // Add association between RevisionEntity and Revision
            $builder->addOneToMany('revisionEntities', 'SoliantEntityAudit\\Entity\\RevisionEntity', 'revision');

            // Add assoication between User and Revision
            $userMetadata = $metadataFactory->getMetadataFor($moduleOptions->getUserEntityClassName());
            $builder
                ->createManyToOne('user', $userMetadata->getName())
                ->addJoinColumn('user_id', $userMetadata->getSingleIdentifierColumnName())
                ->build();

            $metadata->setTableName($moduleOptions->getRevisionTableName());
            return;
        }

#        $builder->createField('audit_id', 'integer')->isPrimaryKey()->generatedValue()->build();
        $identifiers = array();
#        $metadata->setIdentifier(array('audit_id'));

        //  Build a discovered many to many join class
        $joinClasses = $moduleOptions->getJoinClasses();
        if (in_array($className, array_keys($joinClasses))) {

            $builder->createField('id', 'integer')->isPrimaryKey()->generatedValue()->build();

            $builder->addManyToOne('targetRevisionEntity', 'SoliantEntityAudit\\Entity\\RevisionEntity');
            $builder->addManyToOne('sourceRevisionEntity', 'SoliantEntityAudit\\Entity\\RevisionEntity');

            $metadata->setTableName($moduleOptions->getTableNamePrefix() . $joinClasses[$className]['joinTable']['name'] . $moduleOptions->getTableNameSuffix());
//            $metadata->setIdentifier($identifiers);
            return;
        }


        // Get the entity this entity audits
        $metadataClassName = $metadata->getName();
        $metadataClass = new $metadataClassName();

        $auditedClassMetadata = $metadataFactory->getMetadataFor($metadataClass->getAuditedEntityClass());

        try {
            $builder->addManyToOne($moduleOptions->getRevisionEntityFieldName(), 'SoliantEntityAudit\\Entity\\RevisionEntity');
        } catch (MappingException $e) {
            // do nothing
        }

        // Compound keys removed in favor of auditId (audit_id)
        $identifiers[] = $moduleOptions->getRevisionEntityFieldName();

        // Add fields from target to audit entity
        foreach ($auditedClassMetadata->getFieldNames() as $fieldName) {
            $fieldMapping = $auditedClassMetadata->getFieldMapping($fieldName);

            if (! isset($fieldMapping['inherited']) && ! isset($fieldMapping['declared'])) {
                $fieldMapping['nullable'] = true;
                $fieldMapping['quoted'] = true;
                $builder->addField($fieldName, $auditedClassMetadata->getTypeOfField($fieldName), $fieldMapping);
            }

            if ($auditedClassMetadata->isIdentifier($fieldName)) $identifiers[] = $fieldName;
        }

        foreach ($auditedClassMetadata->getAssociationMappings() as $mapping) {
            if (! isset($mapping['inherited']) && ! isset($mapping['declared'])) {
                if (!$mapping['isOwningSide']) continue;

                if (isset($mapping['joinTable'])) {
                    continue;
                }

                if (isset($mapping['joinTableColumns'])) {
                    foreach ($mapping['joinTableColumns'] as $field) {
                        $builder->addField($mapping['fieldName'], 'integer', array('nullable' => true, 'columnName' => $field));
                    }
                } elseif (isset($mapping['joinColumnFieldNames'])) {
                    foreach ($mapping['joinColumnFieldNames'] as $field) {
                        $builder->addField($mapping['fieldName'], 'integer', array('nullable' => true, 'columnName' => $field));
                    }
                } else {
                    throw new \Exception('Unhandled association mapping');
                }
            }
        }

        if ($auditedClassMetadata->isInheritanceTypeJoined() || $auditedClassMetadata->isInheritanceTypeSingleTable()) {
            $metadata->setInheritanceType($auditedClassMetadata->inheritanceType);
            $metadata->setDiscriminatorColumn($auditedClassMetadata->discriminatorColumn);
            $parentAuditClasses = array();
            $subAuditClasses = array();

            foreach ($auditedClassMetadata->parentClasses as $idx => $parentClass) {
                $parentAuditClass = 'SoliantEntityAudit\\Entity\\' . str_replace('\\', '_', $parentClass);
                $parentAuditClasses[$idx] = $parentAuditClass;
            }

            $metadata->setParentClasses($parentAuditClasses);

            foreach ($auditedClassMetadata->subClasses as $idx => $subClass) {
                $subAuditClass = 'SoliantEntityAudit\\Entity\\' . str_replace('\\', '_', $subClass);
                $subAuditClasses[$idx] = $subAuditClass;
            }

            $metadata->setSubClasses($subAuditClasses);

            foreach ($auditedClassMetadata->discriminatorMap as $mapName => $mapClass) {
                $mapAuditClass = 'SoliantEntityAudit\\Entity\\' . str_replace('\\', '_', $mapClass);
                $metadata->addDiscriminatorMapClass($mapName, $mapAuditClass);
            }
        }

        $metadata->setPrimaryTable(array('name' => $moduleOptions->getTableNamePrefix() . $auditedClassMetadata->getTableName() . $moduleOptions->getTableNameSuffix()));
        $metadata->setIdentifier($identifiers);

        return;
    }

    /**
     * Gets the names of all mapped classes known to this driver.
     *
     * @return array The names of all mapped classes known to this driver.
     */
    function getAllClassNames()
    {
        $moduleOptions = \SoliantEntityAudit\Module::getModuleOptions();
        $entityManager = $moduleOptions->getEntityManager();
        $metadataFactory = $entityManager->getMetadataFactory();

        $auditEntities = array();
        foreach ($moduleOptions->getAuditedClassNames() as $name => $targetClassOptions) {
            $auditClassName = "SoliantEntityAudit\\Entity\\" . str_replace('\\', '_', $name);
            $auditEntities[] = $auditClassName;
            $auditedClassMetadata = $metadataFactory->getMetadataFor($name);

            // FIXME:  done in autoloader
            foreach ($auditedClassMetadata->getAssociationMappings() as $mapping) {
                if (isset($mapping['joinTable']['name'])) {
                    $auditJoinTableClassName = "SoliantEntityAudit\\Entity\\" . str_replace('\\', '_', $mapping['joinTable']['name']);
                    $auditEntities[] = $auditJoinTableClassName;
                    $moduleOptions->addJoinClass($auditJoinTableClassName, $mapping);
                }
            }
        }

        // Add revision (manage here rather than separate namespace)
        $auditEntities[] = 'SoliantEntityAudit\\Entity\\Revision';
        $auditEntities[] = 'SoliantEntityAudit\\Entity\\RevisionEntity';

        return $auditEntities;
    }

    /**
     * Whether the class with the specified name should have its metadata loaded.
     * This is only the case if it is either mapped as an Entity or a
     * MappedSuperclass.
     *
     * @param string $className
     * @return boolean
     */
    function isTransient($className) {
        $classNames = array(
            'SoliantEntityAudit\Entity\AbstractAudit'
        );

        return in_array($className, $classNames);
    }
}
