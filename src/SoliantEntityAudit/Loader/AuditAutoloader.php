<?php

namespace SoliantEntityAudit\Loader;

use Zend\Loader\StandardAutoloader
    , Zend\ServiceManager\ServiceManager
    , Zend\Code\Reflection\ClassReflection
    , Zend\Code\Generator\ClassGenerator
    , Zend\Code\Generator\MethodGenerator
    , Zend\Code\Generator\PropertyGenerator
    ;

class AuditAutoloader extends StandardAutoloader
{
    /**
     * Dynamically scope an audit class
     *
     * @param  string $className
     * @return false|string
     */
    public function loadClass($className, $type)
    {
        $moduleOptions = \SoliantEntityAudit\Module::getModuleOptions();
        if (!$moduleOptions) die("could not load class $className");
        $entityManager = $moduleOptions->getEntityManager();

        $auditClass = new ClassGenerator();

        // Add revision reference getter and setter
        $auditClass->addProperty($moduleOptions->getRevisionEntityFieldName(), null, PropertyGenerator::FLAG_PROTECTED);
        $auditClass->addMethod(
            'get' . $moduleOptions->getRevisionEntityFieldName(),
            array(),
            MethodGenerator::FLAG_PUBLIC,
            " return \$this->" .  $moduleOptions->getRevisionEntityFieldName() . ";");

        $auditClass->addMethod(
            'set' . $moduleOptions->getRevisionEntityFieldName(),
            array('value'),
            MethodGenerator::FLAG_PUBLIC,
            " \$this->" .  $moduleOptions->getRevisionEntityFieldName() . " = \$value;\nreturn \$this;
            ");

        //  Build a discovered many to many join class
        $joinClasses = $moduleOptions->getJoinClasses();
        if (in_array($className, array_keys($joinClasses))) {
            $auditClass->setNamespaceName("SoliantEntityAudit\\Entity");
            $auditClass->setName($className);
            $auditClass->setExtendedClass('AbstractAudit');

            $auditClass->addProperty($joinClasses[$className]['fieldName'], null, PropertyGenerator::FLAG_PROTECTED);
            $auditClass->addProperty($joinClasses[$className]['inversedBy'], null, PropertyGenerator::FLAG_PROTECTED);

            // Add function to return the entity class this entity audits
            $auditClass->addMethod(
                'getAuditedEntityClass',
                array(),
                MethodGenerator::FLAG_PUBLIC,
                " return '" . addslashes($className) . "';"
            );

            // Add exchange array method
            $setters = array();
            foreach (array($joinClasses[$className]['fieldName'], $joinClasses[$className]['inversedBy']) as $fieldName) {
                $setters[] = '$this->' . $fieldName . ' = (isset($data["' . $fieldName . '"])) ? $data["' . $fieldName . '"]: null;';
                $arrayCopy[] = "    \"$fieldName\"" . ' => $this->' . $fieldName;
            }

            $auditClass->addMethod(
                'getArrayCopy',
                array(),
                MethodGenerator::FLAG_PUBLIC,
                "return array(\n" . implode(",\n", $arrayCopy) . "\n);"
            );

            $auditClass->addMethod(
                'exchangeArray',
                array('data'),
                MethodGenerator::FLAG_PUBLIC,
                implode("\n", $setters)
            );
#echo '<pre>';
 #print_r($auditClass->generate());die();
            eval($auditClass->generate());
            return;
        }

        // Verify this autoloader is used for target class
        #FIXME:  why is this sent work outside the set namespace?
        foreach($moduleOptions->getAuditedClassNames() as $targetClass => $targetClassOptions) {

             $auditClassName = 'SoliantEntityAudit\\Entity\\' . str_replace('\\', '_', $targetClass);

             if ($auditClassName == $className) {
                 $currentClass = $targetClass;
             }
             $autoloadClasses[] = $auditClassName;
        }
        if (!in_array($className, $autoloadClasses)) return;

        // Get fields from target entity
        $metadataFactory = $entityManager->getMetadataFactory();

        $auditedClassMetadata = $metadataFactory->getMetadataFor($currentClass);
        $fields = $auditedClassMetadata->getFieldNames();

        // Generate audit entity
        foreach ($fields as $field) {
            $auditClass->addProperty($field, null, PropertyGenerator::FLAG_PROTECTED);
        }

        foreach ($auditedClassMetadata->getAssociationNames() as $associationName) {
            $auditClass->addProperty($associationName, null, PropertyGenerator::FLAG_PROTECTED);
        }


        $auditClass->addMethod(
            'getAssociationMappings',
            array(),
            MethodGenerator::FLAG_PUBLIC,
            "return unserialize('" . serialize($auditedClassMetadata->getAssociationMappings()) . "');"
        );

#        echo '<pre>';
#        print_r($auditedClassMetadata->getAssociationMappings());
#        die();


        // Add exchange array method
        $setters = array();
        foreach ($fields as $fieldName) {
            $setters[] = '$this->' . $fieldName . ' = (isset($data["' . $fieldName . '"])) ? $data["' . $fieldName . '"]: null;';
            $arrayCopy[] = "    \"$fieldName\"" . ' => $this->' . $fieldName;
        }

        $auditClass->addMethod(
            'getArrayCopy',
            array(),
            MethodGenerator::FLAG_PUBLIC,
            "return array(\n" . implode(",\n", $arrayCopy) . "\n);"
        );

        $auditClass->addMethod(
            'exchangeArray',
            array('data'),
            MethodGenerator::FLAG_PUBLIC,
            implode("\n", $setters)
        );

        // Add function to return the entity class this entity audits
        $auditClass->addMethod(
            'getAuditedEntityClass',
            array(),
            MethodGenerator::FLAG_PUBLIC,
            " return '" .  addslashes($currentClass) . "';"
        );

        $auditClass->setNamespaceName("SoliantEntityAudit\\Entity");
        $auditClass->setName(str_replace('\\', '_', $currentClass));
        $auditClass->setExtendedClass('AbstractAudit');

#            echo '<pre>';
#            echo($auditClass->generate());

        eval($auditClass->generate());

#            die();

        return true;
    }

}
