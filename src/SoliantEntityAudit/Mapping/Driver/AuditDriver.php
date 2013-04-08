<?php

namespace SoliantEntityAudit\Mapping\Driver;

use Doctrine\Common\Persistence\Mapping\ClassMetadata
    , Doctrine\Common\Persistence\Mapping\Driver\MappingDriver
    , Doctrine\ORM\Mapping\Builder\ClassMetadataBuilder
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
            $builder->addManyToOne('revision', 'SoliantEntityAudit\\Entity\\Revision');
            $builder->addField('entityKeys', 'string');
            $builder->addField('auditEntityClass', 'string');
            $builder->addField('targetEntityClass', 'string');
            $builder->addField('revisionType', 'string');

            $metadata->setTableName($moduleOptions->getRevisionEntityTableName());
            return;
        }

        // Revision is managed here rather than a separate namespace and driver
        if ($className == 'SoliantEntityAudit\\Entity\\Revision') {
            $builder->createField('id', 'integer')->isPrimaryKey()->generatedValue()->build();
            $builder->addField('comment', 'text', array('nullable' => true));
            $builder->addField('timestamp', 'datetime');

            // Add association between RevisionEntity and Revision
            $builder->addOneToMany('revisionEntities', 'SoliantEntityAudit\\Entity\\RevisionEntity', $moduleOptions->getRevisionFieldName());

            // Add assoication between ZfcUser and Revision
            $zfcUserMetadata = $metadataFactory->getMetadataFor($moduleOptions->getZfcUserEntityClassName());
            $builder
                ->createManyToOne('user', $zfcUserMetadata->getName())
                ->addJoinColumn('user_id', $zfcUserMetadata->getSingleIdentifierColumnName())
                ->build();

            $metadata->setTableName($moduleOptions->getRevisionTableName());
            return;
        }

        //  Build a discovered many to many join class
        $joinClasses = $moduleOptions->getJoinClasses();
        if (in_array($className, array_keys($joinClasses))) {
            $builder->addManyToOne($moduleOptions->getRevisionEntityFieldName(), 'SoliantEntityAudit\\Entity\\Revision');
            $identifiers = array($moduleOptions->getRevisionEntityFieldName());

            foreach ($joinClasses[$className]['joinColumns'] as $joinColumn) {
                $builder->addField($joinColumn['name'], 'integer', array('nullable' => true));
                $identifiers[] = $joinColumn['name'];
            }

            foreach ($joinClasses[$className]['inverseJoinColumns'] as $joinColumn) {
                $builder->addField($joinColumn['name'], 'integer', array('nullable' => true));
                $identifiers[] = $joinColumn['name'];
            }

            $metadata->setTableName($moduleOptions->getTableNamePrefix() . $joinClasses[$className]['name'] . $moduleOptions->getTableNameSuffix());
            $metadata->setIdentifier($identifiers);
            return;
        }


        // Get the entity this entity audits
        $metadataClassName = $metadata->getName();
        $metadataClass = new $metadataClassName();

        $auditedClassMetadata = $metadataFactory->getMetadataFor($metadataClass->getAuditedEntityClass());

        $builder->addManyToOne($moduleOptions->getRevisionEntityFieldName(), 'SoliantEntityAudit\\Entity\\RevisionEntity');
        $identifiers = array($moduleOptions->getRevisionEntityFieldName());

        // Add fields from target to audit entity
        foreach ($auditedClassMetadata->getFieldNames() as $fieldName) {
            $builder->addField($fieldName, $auditedClassMetadata->getTypeOfField($fieldName), array('nullable' => true));
            if ($auditedClassMetadata->isIdentifier($fieldName)) $identifiers[] = $fieldName;
        }

        foreach ($auditedClassMetadata->getAssociationMappings() as $mapping) {
            if (!$mapping['isOwningSide']) continue;

            if (isset($mapping['joinTable'])) {
                continue;
                # print_r($mapping['joinTable']);
                # die('driver');
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

        $metadata->setTableName($moduleOptions->getTableNamePrefix() . $auditedClassMetadata->getTableName() . $moduleOptions->getTableNameSuffix());
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

            foreach ($auditedClassMetadata->getAssociationMappings() as $mapping) {
                if (isset($mapping['joinTable'])) {
                    $auditJoinTableClassName = "SoliantEntityAudit\\Entity\\" . str_replace('\\', '_', $mapping['joinTable']['name']);
                    $auditEntities[] = $auditJoinTableClassName;
                    $moduleOptions->addJoinClass($auditJoinTableClassName, $mapping['joinTable']);
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
        return true;
    }
}
