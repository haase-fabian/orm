<?php
/*
 *  $Id$
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the LGPL. For more information, see
 * <http://www.phpdoctrine.org>.
 */

#namespace Doctrine::ORM::Mapping;

#use Doctrine::ORM::EntityManager;

/**
 * A <tt>ClassMetadata</tt> instance holds all the information (metadata) of an entity and
 * it's associations and how they're mapped to the relational database.
 * It is the backbone of Doctrine's metadata mapping.
 *
 * @author Roman Borschel <roman@code-factory.org>
 * @since 2.0
 * @todo Rename to ClassDescriptor?
 */
class Doctrine_ClassMetadata implements Doctrine_Configurable, Serializable
{
    /* The inheritance mapping types */
    const INHERITANCE_TYPE_NONE = 'none';
    const INHERITANCE_TYPE_JOINED = 'joined';
    const INHERITANCE_TYPE_SINGLE_TABLE = 'singleTable';
    const INHERITANCE_TYPE_TABLE_PER_CLASS = 'tablePerClass';
    
    /**
     * Inheritance types enumeration.
     *
     * @var array
     */
    protected static $_inheritanceTypes = array(
        self::INHERITANCE_TYPE_NONE,
        self::INHERITANCE_TYPE_JOINED,
        self::INHERITANCE_TYPE_SINGLE_TABLE,
        self::INHERITANCE_TYPE_TABLE_PER_CLASS
    );
    
    /* The Id generator types. TODO: belongs more in a DBAL class */
    const GENERATOR_TYPE_AUTO = 'auto';
    const GENERATOR_TYPE_SEQUENCE = 'sequence';
    const GENERATOR_TYPE_TABLE = 'table';
    const GENERATOR_TYPE_IDENTITY = 'identity';
    const GENERATOR_TYPE_NONE = 'none';
    
    /**
     * Id Generator types enumeration.
     *
     * @var array
     */
    protected static $_generatorTypes = array(
        self::GENERATOR_TYPE_AUTO,
        self::GENERATOR_TYPE_SEQUENCE,
        self::GENERATOR_TYPE_TABLE,
        self::GENERATOR_TYPE_IDENTITY,
        self::GENERATOR_TYPE_NONE
    );
    
    /**
     * The name of the entity class.
     *
     * @var string
     */
    protected $_entityName;

    /**
     * The name of the entity class that is at the root of the entity inheritance
     * hierarchy. If the entity is not part of an inheritance hierarchy this is the same
     * as $_entityName.
     *
     * @var string
     */
    protected $_rootEntityName;

    /**
     * The name of the custom mapper class used for the entity class.
     * (Optional).
     *
     * @var string
     */
    protected $_customRepositoryClassName;

    /**
     * The EntityManager.
     * 
     * @var Doctrine::ORM::EntityManager
     */
    protected $_em;

    /**
     * The names of the parent classes (ancestors).
     * 
     * @var array
     */
    protected $_parentClasses = array();

    /**
     * The names of all subclasses.
     * 
     * @var array
     */
    protected $_subClasses = array();

    /**
     * The field names of all fields that are part of the identifier/primary key
     * of the described entity class.
     *
     * @var array
     */
    protected $_identifier = array();
    
    /**
     * The inheritance mapping type used by the class.
     *
     * @var integer
     */
    protected $_inheritanceType = self::INHERITANCE_TYPE_NONE;
    
    /**
     * The Id generator type used by the class.
     *
     * @var string
     */
    protected $_generatorType = self::GENERATOR_TYPE_NONE;

    /**
     * An array containing all behaviors attached to the class.
     *
     * @see Doctrine_Template
     * @var array $_templates
     * @todo Unify under 'Behaviors'.
     */
    //protected $_behaviors = array();
    
    /**
     * The field mappings of the class.
     * Keys are field names and values are mapping definitions.
     *
     * The mapping definition array has the following values:
     * 
     * - <b>fieldName</b> (string)
     * The name of the field in the Entity. 
     * 
     * - <b>type</b> (string)
     * The database type of the column. Can be one of Doctrine's portable types.
     * 
     * - <b>columnName</b> (string, optional)
     * The column name. Optional. Defaults to the field name.
     * 
     * - <b>length</b> (integer, optional)
     * The database length of the column. Optional. Defaults to Doctrine's 
     * default values for the portable types.
     * 
     * - <b>id</b> (boolean, optional)
     * Marks the field as the primary key of the Entity. Multiple fields of an
     * entity can have the id attribute, forming a composite key.
     * 
     * - <b>generatorType</b> (string, optional, requires: id)
     * The generation type used for the field. Optional. Can only be applied on
     * fields that are marked with the 'id' attribute. The possible values are:
     * auto, identity, sequence, table.
     * 
     * - <b>generator</b> (array, optional, requires: generationType=table|sequence)
     * The generator options for a table or sequence generator. Can only be applied
     * on fields that have a generationType of 'table' or 'sequence'.
     * 
     * - <b>nullable</b> (boolean, optional)
     * Whether the column is nullable. Defaults to TRUE.
     * 
     * - <b>columnDefinition</b> (string, optional)
     * The SQL fragment that is used when generating the DDL for the column.
     * 
     * - <b>precision</b> (integer, optional)
     * The precision of a decimal column. Only valid if the column type is decimal.
     * 
     * - <b>scale</b> (integer, optional)
     * The scale of a decimal column. Only valid if the column type is decimal.
     *
     * @var array
     */    
    protected $_fieldMappings = array();
    
    /**
     * The mapped embedded values (value objects).
     *
     * @var array
     * @TODO Implementation (Value Object support)
     */
    //protected $_embeddedValueMappings = array();

    /**
     * Enter description here...
     *
     * @var array
     */
    protected $_attributes = array('loadReferences' => true);
    
    /**
     * An array of field names. used to look up field names from column names.
     * Keys are column names and values are field names.
     * This is the reverse lookup map of $_columnNames.
     *
     * @var array
     */
    protected $_fieldNames = array();

    /**
     * An array of column names. Keys are field names and values column names.
     * Used to look up column names from field names.
     * This is the reverse lookup map of $_fieldNames.
     *
     * @var array
     */
    protected $_columnNames = array();
    
    /**
     * Map that maps lowercased column names to field names.
     * Mainly used during hydration because Doctrine enforces PDO_CASE_LOWER
     * for portability.
     *
     * @var array
     */
    protected $_lcColumnToFieldNames = array();
    
    /**
     * Enter description here...
     *
     * @var unknown_type
     */
    //protected $_subclassFieldNames = array();

    /**
     * Relation parser object. Manages the relations for the class.
     *
     * @var Doctrine_Relation_Parser $_parser
     * @todo Remove.
     */
    protected $_parser;

    /**
     * Inheritance options.
     */
    protected $_inheritanceOptions = array(
    // JOINED & TABLE_PER_CLASS options
            'discriminatorColumn' => null,
            'discriminatorMap'    => array(),
    // JOINED options
            'joinSubclasses'      => true
    );

    /**
     * Specific options that can be set for the database table the class is mapped to.
     * Some of them are dbms specific and they are only used if the table is generated
     * by Doctrine (NOT when using Migrations).
     *
     *      -- type                         table type (mysql example: INNODB)
     *
     *      -- charset                      character set
     *
     *      -- checks                       the check constraints of this table, eg. 'price > dicounted_price'
     *
     *      -- collation                    collation attribute
     *
     *      -- indexes                      the index definitions of this table
     *
     *      -- sequenceName                 Some databases need sequences instead of auto incrementation primary keys,
     *                                      you can set specific sequence for your table by calling setOption('sequenceName', $seqName)
     *                                      where $seqName is the name of the desired sequence
     */
    protected $_tableOptions = array(
            'tableName'      => null,
            'sequenceName'   => null,
            'type'           => null,
            'charset'        => null,
            'collation'      => null,
            'collate'        => null,
            'indexes'        => array(),
            'checks'         => array()
    );
    
    /**
     * The cached lifecycle listeners. There is only one instance of each
     * listener class at any time.
     *
     * @var array
     */
    protected $_lifecycleListenerInstances = array();

    /**
     * The registered lifecycle callbacks for Entities of this class.
     *
     * @var array
     */
    protected $_lifecycleCallbacks = array();
    
    /**
     * The registered lifecycle listeners for Entities of this class.
     *
     * @var array
     */
    protected $_lifecycleListeners = array();
    
    /**
     * The association mappings.
     *
     * @var array
     */
    protected $_associationMappings = array();
    
    /**
     * Flag indicating whether the identifier/primary key of the class is composite.
     *
     * @var boolean
     */
    protected $_isIdentifierComposite = false;

    /**
     * Constructs a new ClassMetadata instance.
     *
     * @param string $entityName  Name of the entity class the metadata info is used for.
     */
    public function __construct($entityName, Doctrine_EntityManager $em)
    {
        $this->_entityName = $entityName;
        $this->_rootEntityName = $entityName;
        $this->_em = $em;
        
        $this->_parser = new Doctrine_Relation_Parser($this);
    }

    /**
     * @deprecated
     */
    public function getConnection()
    {
        return $this->_em;
    }
    
    public function getEntityManager()
    {
        return $this->_em;
    }

    /**
     * getComponentName
     *
     * @return string
     */
    public function getClassName()
    {
        return $this->_entityName;
    }

    /**
     * Gets the name of the root class of the entity hierarchy. If the entity described
     * by the ClassMetadata is not participating in a hierarchy, this is the same as the
     * name returned by {@link getClassName()}.
     *
     * @return string
     */
    public function getRootClassName()
    {
        return $this->_rootEntityName;
    }

    /**
     * @deprecated
     */
    public function getComponentName()
    {
        return $this->getClassName();
    }

    /**
     * Checks whether a field is part of the identifier/primary key field(s).
     *
     * @param string $fieldName  The field name
     * @return boolean  TRUE if the field is part of the table identifier/primary key field(s),
     *                  FALSE otherwise.
     */
    public function isIdentifier($fieldName)
    {
        if ( ! $this->_isIdentifierComposite) {
            return $fieldName === $this->_identifier[0];
        }
        return in_array($fieldName, $this->_identifier);
    }

    /**
     * Check if the class has a composite identifier.
     *
     * @param string $fieldName  The field name
     * @return boolean  TRUE if the identifier is composite, FALSE otherwise.
     */
    public function isIdentifierComposite()
    {
        return $this->_isIdentifierComposite;
    }

    /**
     * Check if the field is unique.
     *
     * @param string $fieldName  The field name
     * @return boolean  TRUE if the field is unique, FALSE otherwise.
     */
    public function isUniqueField($fieldName)
    {
        $mapping = $this->getFieldMapping($fieldName);

        if ($mapping !== false) {
            return isset($mapping['unique']) && $mapping['unique'] == true;
        }

        return false;
    }

    /**
     * Check if the field is not null.
     *
     * @param string $fieldName  The field name
     * @return boolean  TRUE if the field is not null, FALSE otherwise.
     */
    public function isNotNull($fieldName)
    {
        $mapping = $this->getFieldMapping($fieldName);

        if ($mapping !== false) {
            return isset($mapping['notnull']) && $mapping['notnull'] == true;
        }

        return false;
    }

    /**
     * addIndex
     *
     * adds an index to this table
     *
     * @return void
     * @deprecated
     * @todo Should be done through setTableOption().
     */
    public function addIndex($index, array $definition)
    {
        $this->_tableOptions['indexes'][$index] = $definition;
    }

    /**
     * getIndex
     *
     * @return array|boolean        array on success, FALSE on failure
     * @todo Should be done through getTableOption().
     * @deprecated
     */
    public function getIndex($index)
    {
        if (isset($this->_tableOptions['indexes'][$index])) {
            return $this->_tableOptions['indexes'][$index];
        }

        return false;
    }

    /**
     * setOption
     * sets an option and returns this object in order to
     * allow flexible method chaining
     *
     * @see Doctrine_Table::$_options   for available options
     * @param string $name              the name of the option to set
     * @param mixed $value              the value of the option
     * @return Doctrine_Table           this object
     * @deprecated
     */
    public function setOption($name, $value)
    {
        $this->_options[$name] = $value;
    }

    /**
     * Sets a table option.
     */
    public function setTableOption($name, $value)
    {
        if ( ! array_key_exists($name, $this->_tableOptions)) {
            throw new Doctrine_ClassMetadata_Exception("Unknown table option: '$name'.");
        }
        $this->_tableOptions[$name] = $value;
    }

    /**
     * Gets a table option.
     */
    public function getTableOption($name)
    {
        if ( ! array_key_exists($name, $this->_tableOptions)) {
            throw new Doctrine_ClassMetadata_Exception("Unknown table option: '$name'.");
        }

        return $this->_tableOptions[$name];
    }

    /*public function getBehaviorForMethod($method)
    {
        return (isset($this->_invokedMethods[$method])) ?
        $this->_invokedMethods[$method] : false;
    }
    public function addBehaviorMethod($method, $behavior)
    {
        $this->_invokedMethods[$method] = $class;
    }*/

    /**
     * returns the value of given option
     *
     * @param string $name  the name of the option
     * @return mixed        the value of given option
     */
    public function getOption($name)
    {
        if (isset($this->_options[$name])) {
            return $this->_options[$name];
        } else if (isset($this->_tableOptions[$name])) {
            return $this->_tableOptions[$name];
        }
        return null;
    }

    /**
     * getOptions
     * returns all options of this table and the associated values
     *
     * @return array    all options and their values
     */
    public function getOptions()
    {
        return $this->_options;
    }

    /**
     * getTableOptions
     * returns all table options.
     *
     * @return array    all options and their values
     */
    public function getTableOptions()
    {
        return $this->_tableOptions;
    }

    /**
     * Gets a column name for a field name.
     * If the column name for the field cannot be found, the given field name
     * is returned.
     *
     * @param string $alias  The field name.
     * @return string  The column name.
     */
    public function getColumnName($fieldName)
    {
        return isset($this->_columnNames[$fieldName]) ?
                $this->_columnNames[$fieldName] : $fieldName;
    }

    /**
     * Gets the mapping of a (regular) fields that holds some data but not a
     * reference to another object.
     *
     * @param string $fieldName  The field name.
     * @return array  The mapping.
     */
    public function getFieldMapping($fieldName)
    {
        if ( ! isset($this->_fieldMappings[$fieldName])) {
            throw Doctrine_MappingException::mappingNotFound($fieldName);
        }
        
        return $this->_fieldMappings[$fieldName];
    }
    
    /**
     * Gets the mapping of an association.
     *
     * @param string $fieldName  The field name that represents the association in
     *                           the object model.
     * @return Doctrine::ORM::Mapping::AssociationMapping  The mapping.
     */
    public function getAssociationMapping($fieldName)
    {
        if ( ! isset($this->_associationMappings[$fieldName])) {
            throw Doctrine_MappingException::mappingNotFound($fieldName);
        }
        
        return $this->_associationMappings[$fieldName];
    }
    
    /**
     * Gets all association mappings of the class.
     *
     * @return array
     */
    public function getAssociationMappings()
    {
        return $this->_associationMappings;
    }

    /**
     * Gets the field name for a column name.
     * If no field name can be found the column name is returned.
     *
     * @param string $columnName    column name
     * @return string               column alias
     */
    public function getFieldName($columnName)
    {
        return isset($this->_fieldNames[$columnName]) ?
                $this->_fieldNames[$columnName] : $columnName;
    }
    
    /**
     * Gets the field name for a completely lowercased column name.
     * Mainly used during hydration.
     *
     * @param string $lcColumnName
     * @return string
     * @todo Better name.
     */
    public function getFieldNameForLowerColumnName($lcColumnName)
    {
        return isset($this->_lcColumnToFieldNames[$lcColumnName]) ?
                $this->_lcColumnToFieldNames[$lcColumnName] : $lcColumnName;
    }
    
    public function hasLowerColumn($lcColumnName)
    {
        return isset($this->_lcColumnToFieldNames[$lcColumnName]);
    }
    
    /**
     * Looks up the field name for a (lowercased) column name.
     * 
     * This is mostly used during hydration, because we want to make the
     * conversion to field names while iterating over the result set for best
     * performance. By doing this at that point, we can avoid re-iterating over
     * the data just to convert the column names to field names.
     * 
     * However, when this is happening, we don't know the real
     * class name to instantiate yet (the row data may target a sub-type), hence
     * this method looks up the field name in the subclass mappings if it's not
     * found on this class mapping.
     * This lookup on subclasses is costly but happens only *once* for a column
     * during hydration because the hydrator caches effectively.
     * 
     * @return string  The field name.
     * @throws Doctrine::ORM::Exceptions::ClassMetadataException If the field name could
     *         not be found.
     */
    public function lookupFieldName($lcColumnName)
    {
        if (isset($this->_lcColumnToFieldNames[$lcColumnName])) {
            return $this->_lcColumnToFieldNames[$lcColumnName];
        }/* else if (isset($this->_subclassFieldNames[$lcColumnName])) {
            return $this->_subclassFieldNames[$lcColumnName];
        }*/
        
        foreach ($this->getSubclasses() as $subClass) {
            $subClassMetadata = $this->_em->getClassMetadata($subClass);
            if ($subClassMetadata->hasLowerColumn($lcColumnName)) {
                /*$this->_subclassFieldNames[$lcColumnName] = $subClassMetadata->
                        getFieldNameForLowerColumnName($lcColumnName);
                return $this->_subclassFieldNames[$lcColumnName];*/
                return $subClassMetadata->getFieldNameForLowerColumnName($lcColumnName);
            }
        }

        throw new Doctrine_ClassMetadata_Exception("No field name found for column name '$lcColumnName' during lookup.");
    }
    
    /**
     * Adds a field mapping.
     *
     * @param array $mapping
     */
    public function mapField(array $mapping)
    {
        $mapping = $this->_validateAndCompleteFieldMapping($mapping);
        if (isset($this->_fieldMappings[$mapping['fieldName']])) {
            throw Doctrine_MappingException::duplicateFieldMapping();
        }
        $this->_fieldMappings[$mapping['fieldName']] = $mapping;
    }
    
    /**
     * Overrides an existant field mapping.
     * Used i.e. by Entity classes deriving from another Entity class that acts
     * as a mapped superclass to refine the basic mapping.
     *
     * @param array $newMapping
     * @todo Implementation.
     */
    public function overrideFieldMapping(array $newMapping)
    {
        //...
    }
    
    /**
     * Validates & completes the field mapping. Default values are applied here.
     *
     * @param array $mapping
     * @return array
     */
    private function _validateAndCompleteFieldMapping(array $mapping)
    {
        // Check mandatory fields
        if ( ! isset($mapping['fieldName'])) {
            throw Doctrine_MappingException::missingFieldName();
        }
        if ( ! isset($mapping['type'])) {
            throw Doctrine_MappingException::missingType();
        }
        
        // Complete fieldName and columnName mapping
        if ( ! isset($mapping['columnName'])) {
            $mapping['columnName'] = $mapping['fieldName'];
        }
        $lcColumnName = strtolower($mapping['columnName']);

        $this->_columnNames[$mapping['fieldName']] = $mapping['columnName'];
        $this->_fieldNames[$mapping['columnName']] = $mapping['fieldName'];
        $this->_lcColumnToFieldNames[$lcColumnName] = $mapping['fieldName'];
        
        // Complete length mapping
        if ( ! isset($mapping['length'])) {
            $mapping['length'] = $this->_getDefaultLength($mapping['type']);
        }
        
        // Complete id mapping
        if (isset($mapping['id']) && $mapping['id'] === true) {
            if ( ! in_array($mapping['fieldName'], $this->_identifier)) {
                $this->_identifier[] = $mapping['fieldName'];
            }
            if (isset($mapping['generatorType'])) {
                if ( ! in_array($mapping['generatorType'], self::$_generatorTypes)) {
                    throw Doctrine_MappingException::invalidGeneratorType($mapping['generatorType']);
                } else if (count($this->_identifier) > 1) {
                    throw Doctrine_MappingException::generatorNotAllowedWithCompositeId();
                }
                $this->_generatorType = $mapping['generatorType'];
            }
            // TODO: validate/complete 'generator' mapping
            
            // Check for composite key
            if ( ! $this->_isIdentifierComposite && count($this->_identifier) > 1) {
                $this->_isIdentifierComposite = true;
            }
            
        }
        
        return $mapping;
    }
    
    /**
     * Gets the default length for a column type.
     *
     * @param string $type
     * @return mixed
     */
    private function _getDefaultLength($type)
    {
        switch ($type) {
            case 'string':
            case 'clob':
            case 'float':
            case 'integer':
            case 'array':
            case 'object':
            case 'blob':
            case 'gzip':
                // use php int max
                return 2147483647;
            case 'boolean':
                return 1;
            case 'date':
                // YYYY-MM-DD ISO 8601
                return 10;
            case 'time':
                // HH:NN:SS+00:00 ISO 8601
                return 14;
            case 'timestamp':
                // YYYY-MM-DDTHH:MM:SS+00:00 ISO 8601
                return 25;
        }
    }
    
    /**
     * Maps an embedded value object.
     *
     * @todo Implementation.
     */
    public function mapEmbeddedValue()
    {
        //...
    }

    /**
     * Gets the names of all validators that are applied on a field.
     *
     * @param string  The field name.
     * @return array  The names of all validators that are applied on the specified field.
     */
    /*public function getFieldValidators($fieldName)
    {
        return isset($this->_fieldMappings[$fieldName]['validators']) ?
                $this->_fieldMappings[$fieldName]['validators'] : array();
    }*/

    /**
     * Gets the identifier (primary key) field names of the class.
     *
     * @return mixed
     * @deprecated Use getIdentifierFieldNames()
     */
    public function getIdentifier()
    {
        return $this->_identifier;
    }

    /**
     * Gets the identifier (primary key) field names of the class.
     *
     * @return mixed
     */
    public function getIdentifierFieldNames()
    {
        return $this->_identifier;
    }
    
    /**
     * Gets the name of the single id field. Note that this only works on
     * entity classes that have a single-field pk.
     *
     * @return string
     */
    public function getSingleIdentifierFieldName()
    {
        if ($this->_isIdentifierComposite) {
            throw new Doctrine_Exception("Calling getSingleIdentifierFieldName "
                    . "on a class that uses a composite identifier is not allowed.");
        }
        return $this->_identifier[0];
    }

    public function setIdentifier(array $identifier)
    {
        $this->_identifier = $identifier;
    }

    /**
     * Checks whether the class has a (mapped) field with a certain name.
     * 
     * @return boolean
     */
    public function hasField($fieldName)
    {
        return isset($this->_columnNames[$fieldName]);
    }

    /**
     * Gets the custom accessor of a field.
     * 
     * @return string  The name of the accessor (getter) method or NULL if the field does
     *                 not have a custom accessor.
     */
    public function getCustomAccessor($fieldName)
    {
        return isset($this->_fieldMappings[$fieldName]['accessor']) ?
                $this->_fieldMappings[$fieldName]['accessor'] : null;
    }

    /**
     * Gets the custom mutator of a field.
     * 
     * @return string  The name of the mutator (setter) method or NULL if the field does
     *                 not have a custom mutator.
     */
    public function getCustomMutator($fieldName)
    {
        return isset($this->_fieldMappings[$fieldName]['mutator']) ?
                $this->_fieldMappings[$fieldName]['mutator'] : null;
    }
    
    /**
     * Gets all field mappings.
     *
     * @return array
     */
    public function getFieldMappings()
    {
        return $this->_fieldMappings;
    }

    /**
     * Gets an array containing all the column names.
     *
     * @return array
     */
    public function getColumnNames(array $fieldNames = null)
    {
        if ($fieldNames === null) {
            return array_keys($this->_fieldNames);
        } else {
            $columnNames = array();
            foreach ($fieldNames as $fieldName) {
                $columnNames[] = $this->getColumnName($fieldName);
            } 
            return $columnNames;
        }
    }

    /**
     * Returns an array with all the identifier column names.
     *
     * @return array
     */
    public function getIdentifierColumnNames()
    {
        return $this->getColumnNames((array) $this->getIdentifier());
    }

    /**
     * Returns an array containing all the field names.
     *
     * @return array
     */
    public function getFieldNames()
    {
        return array_values($this->_fieldNames);
    }
    
    /**
     * Gets the Id generator type used by the class.
     *
     * @return string
     */
    public function getIdGeneratorType()
    {
        return $this->_generatorType;
    }
    
    /**
     * Checks whether the class uses an Id generator.
     *
     * @return boolean  TRUE if the class uses an Id generator, FALSE otherwise.
     */
    public function usesIdGenerator()
    {
        return $this->_generatorType != self::GENERATOR_TYPE_NONE;
    }
    
    /**
     * Checks whether the class uses an identity column for the Id generation.
     *
     * @return boolean TRUE if the class uses the IDENTITY generator, FALSE otherwise.
     */
    public function isIdGeneratorIdentity()
    {
        return $this->_generatorType == self::GENERATOR_TYPE_IDENTITY;
    }
    
    /**
     * Checks whether the class uses a sequence for id generation.
     *
     * @return boolean TRUE if the class uses the SEQUENCE generator, FALSE otherwise.
     */
    public function isIdGeneratorSequence()
    {
        return $this->_generatorType == self::GENERATOR_TYPE_SEQUENCE;
    }
    
    /**
     * Checks whether the class uses a table for id generation.
     *
     * @return boolean  TRUE if the class uses the TABLE generator, FALSE otherwise.
     */
    public function isIdGeneratorTable()
    {
        $this->_generatorType == self::GENERATOR_TYPE_TABLE;
    }
    
    /**
     * Checks whether the class has a natural identifier/pk (which means it does
     * not use any Id generator.
     *
     * @return boolean
     */
    public function isIdentifierNatural()
    {
        return $this->_generatorType == self::GENERATOR_TYPE_NONE;
    }
    
    /**
     * Gets the type of a field.
     *
     * @param string $fieldName
     * @return string
     */
    public function getTypeOfField($fieldName)
    {
        return isset($this->_fieldMappings[$fieldName]) ?
                $this->_fieldMappings[$fieldName]['type'] : false;
    }

    /**
     * getTypeOfColumn
     *
     * @return mixed  The column type or FALSE if the type cant be determined.
     */
    public function getTypeOfColumn($columnName)
    {
        return $this->getTypeOfField($this->getFieldName($columnName));
    }

    /**
     * Gets the (maximum) length of a field.
     */
    public function getFieldLength($fieldName)
    {
        return $this->_fieldMappings[$fieldName]['length'];
    }

    /**
     * getTableName
     *
     * @return void
     */
    public function getTableName()
    {
        return $this->getTableOption('tableName');
    }

    public function getInheritedFields()
    {

    }

    /**
     * Adds a named query.
     *
     * @param string $name  The name under which the query gets registered.
     * @param string $query The DQL query.
     * @todo Implementation.
     */
    public function addNamedQuery($name, $query)
    {
        //...
    }

    /**
     * getBehaviors
     * returns all behaviors attached to the class.
     *
     * @return array     an array containing all templates
     * @todo Unify under 'Behaviors'
     */
    /*public function getBehaviors()
    {
        return $this->_behaviors;
    }*/

    /**
     * Gets the inheritance type used by the class.
     *
     * @return integer
     */
    public function getInheritanceType()
    {
        return $this->_inheritanceType;
    }

    /**
     * Sets the subclasses of the class.
     * All entity classes that participate in a hierarchy and have subclasses
     * need to declare them this way.
     *
     * @param array $subclasses  The names of all subclasses.
     */
    public function setSubclasses(array $subclasses)
    {
        $this->_subClasses = $subclasses;
    }

    /**
     * Gets the names of all subclasses.
     *
     * @return array  The names of all subclasses.
     */
    public function getSubclasses()
    {
        return $this->_subClasses;
    }

    /**
     * Checks whether the class has any persistent subclasses.
     *
     * @return boolean TRUE if the class has one or more persistent subclasses, FALSE otherwise.
     */
    public function hasSubclasses()
    {
        return ! $this->_subClasses;
    }

    /**
     * Gets the names of all parent classes.
     *
     * @return array  The names of all parent classes.
     */
    public function getParentClasses()
    {
        return $this->_parentClasses;
    }

    /**
     * Sets the parent class names.
     * Assumes that the class names in the passed array are in the order:
     * directParent -> directParentParent -> directParentParentParent ... -> root.
     */
    public function setParentClasses(array $classNames)
    {
        $this->_parentClasses = $classNames;
        if (count($classNames) > 0) {
            $this->_rootEntityName = array_pop($classNames);
        }
    }

    /**
     * Checks whether the class has any persistent parent classes.
     *
     * @return boolean TRUE if the class has one or more persistent parent classes, FALSE otherwise.
     */
    public function hasParentClasses()
    {
        return ! $this->_parentClasses;
    }

    /**
     * Sets the inheritance type used by the class and it's subclasses.
     *
     * @param integer $type
     */
    public function setInheritanceType($type, array $options = array())
    {
        if ($parentClassNames = $this->getParentClasses()) {
            throw new Doctrine_MappingException("All classes in an inheritance hierarchy"
                . " must share the same inheritance mapping type and this type must be set"
                . " in the root class of the hierarchy.");
        }
        if ( ! in_array($type, self::$_inheritanceTypes)) {
            throw Doctrine_MappingException::invalidInheritanceType($type);
        }
        
        if ($type == Doctrine::INHERITANCE_TYPE_SINGLE_TABLE ||
                $type == Doctrine::INHERITANCE_TYPE_JOINED) {
            $this->_checkRequiredDiscriminatorOptions($options);
        }

        $this->_inheritanceType = $type;
        foreach ($options as $name => $value) {
            $this->setInheritanceOption($name, $value);
        }
    }

    /**
     * Checks if the 2 options 'discriminatorColumn' and 'discriminatorMap' are present.
     * If either of them is missing an exception is thrown.
     *
     * @param array $options  The options.
     * @throws Doctrine_ClassMetadata_Exception  If at least one of the required discriminator
     *                                           options is missing.
     */
    private function _checkRequiredDiscriminatorOptions(array $options)
    {
        if ( ! isset($options['discriminatorColumn'])) {
            throw new Doctrine_ClassMetadata_Exception("Missing option 'discriminatorColumn'."
            . " Inheritance types JOINED and SINGLE_TABLE require this option.");
        } else if ( ! isset($options['discriminatorMap'])) {
            throw new Doctrine_ClassMetadata_Exception("Missing option 'discriminatorMap'."
            . " Inheritance types JOINED and SINGLE_TABLE require this option.");
        }
    }

    /**
     * Gets an inheritance option.
     *
     */
    public function getInheritanceOption($name)
    {
        if ( ! array_key_exists($name, $this->_inheritanceOptions)) {
            throw new Doctrine_ClassMetadata_Exception("Unknown inheritance option: '$name'.");
        }

        return $this->_inheritanceOptions[$name];
    }

    /**
     * Gets all inheritance options.
     */
    public function getInheritanceOptions()
    {
        return $this->_inheritanceOptions;
    }

    /**
     * Sets an inheritance option.
     */
    public function setInheritanceOption($name, $value)
    {
        if ( ! array_key_exists($name, $this->_inheritanceOptions)) {
            throw new Doctrine_ClassMetadata_Exception("Unknown inheritance option: '$name'.");
        }

        if ($this->_inheritanceType == 'joined' || $this->_inheritanceType == 'singleTable') {
            switch ($name) {
                case 'discriminatorColumn':
                    if ($value !== null && ! is_string($value)) {
                        throw new Doctrine_ClassMetadata_Exception("Invalid value '$value' for option"
                        . " 'discriminatorColumn'.");
                    }
                    break;
                case 'discriminatorMap':
                    if ( ! is_array($value)) {
                        throw new Doctrine_ClassMetadata_Exception("Value for option 'discriminatorMap'"
                        . " must be an array.");
                    }
                    break;
                    // ... further validation checks as needed
                default:
                    throw Doctrine_MappingException::invalidInheritanceOption($name);
            }
        }

        $this->_inheritanceOptions[$name] = $value;
    }

    /**
     * export
     * exports this class to the database based on its mapping.
     *
     * @throws Doctrine_Connection_Exception    If some error other than Doctrine::ERR_ALREADY_EXISTS
     *                                          occurred during the create table operation.
     * @return boolean                          Whether or not the export operation was successful
     *                                          false if table already existed in the database.
     */
    public function export()
    {
        $this->_em->export->exportTable($this);
    }

    /**
     * getExportableFormat
     * Returns an array with all the information needed to create the main database table
     * for the class.
     *
     * @return array
     */
    public function getExportableFormat($parseForeignKeys = true)
    {
        $columns = array();
        $primary = array();
        $allColumns = $this->getColumns();

        // If the class is part of a Single Table Inheritance hierarchy, collect the fields
        // of all classes in the hierarchy.
        if ($this->_inheritanceType == Doctrine::INHERITANCE_TYPE_SINGLE_TABLE) {
            $parents = $this->getParentClasses();
            if ($parents) {
                $rootClass = $this->_em->getClassMetadata(array_pop($parents));
            } else {
                $rootClass = $this;
            }
            $subClasses = $rootClass->getSubclasses();
            foreach ($subClasses as $subClass) {
                $subClassMetadata = $this->_em->getClassMetadata($subClass);
                $allColumns = array_merge($allColumns, $subClassMetadata->getColumns());
            }
        } else if ($this->_inheritanceType == Doctrine::INHERITANCE_TYPE_JOINED) {
            // Remove inherited, non-pk fields. They're not in the table of this class
            foreach ($allColumns as $name => $definition) {
                if (isset($definition['primary']) && $definition['primary'] === true) {
                    if ($this->getParentClasses() && isset($definition['autoincrement'])) {
                        unset($allColumns[$name]['autoincrement']);
                    }
                    continue;
                }
                if (isset($definition['inherited']) && $definition['inherited'] === true) {
                    unset($allColumns[$name]);
                }
            }
        } else if ($this->_inheritanceType == Doctrine::INHERITANCE_TYPE_TABLE_PER_CLASS) {
            // If this is a subclass, just remove existing autoincrement options on the pk
            if ($this->getParentClasses()) {
                foreach ($allColumns as $name => $definition) {
                    if (isset($definition['primary']) && $definition['primary'] === true) {
                        if (isset($definition['autoincrement'])) {
                            unset($allColumns[$name]['autoincrement']);
                        }
                    }
                }
            }
        }

        // Convert enum & boolean default values
        foreach ($allColumns as $name => $definition) {
            switch ($definition['type']) {
                case 'enum':
                    if (isset($definition['default'])) {
                        $definition['default'] = $this->enumIndex($name, $definition['default']);
                    }
                    break;
                case 'boolean':
                    if (isset($definition['default'])) {
                        $definition['default'] = $this->_em->convertBooleans($definition['default']);
                    }
                    break;
            }
            $columns[$name] = $definition;

            if (isset($definition['primary']) && $definition['primary']) {
                $primary[] = $name;
            }
        }

        // Collect foreign keys from the relations
        $options['foreignKeys'] = array();
        if ($parseForeignKeys && $this->getAttribute(Doctrine::ATTR_EXPORT)
                & Doctrine::EXPORT_CONSTRAINTS) {
            $constraints = array();
            $emptyIntegrity = array('onUpdate' => null, 'onDelete' => null);
            foreach ($this->getRelations() as $name => $relation) {
                $fk = $relation->toArray();
                $fk['foreignTable'] = $relation->getTable()->getTableName();

                if ($relation->getTable() === $this && in_array($relation->getLocal(), $primary)) {
                    if ($relation->hasConstraint()) {
                        throw new Doctrine_Table_Exception("Badly constructed integrity constraints.");
                    }
                    continue;
                }

                $integrity = array('onUpdate' => $fk['onUpdate'],
                                   'onDelete' => $fk['onDelete']);

                if ($relation instanceof Doctrine_Relation_LocalKey) {
                    $def = array('local'        => $relation->getLocal(),
                                 'foreign'      => $relation->getForeign(),
                                 'foreignTable' => $relation->getTable()->getTableName());

                    if (($key = array_search($def, $options['foreignKeys'])) === false) {
                        $options['foreignKeys'][] = $def;
                        $constraints[] = $integrity;
                    } else {
                        if ($integrity !== $emptyIntegrity) {
                            $constraints[$key] = $integrity;
                        }
                    }
                }
            }

            foreach ($constraints as $k => $def) {
                $options['foreignKeys'][$k] = array_merge($options['foreignKeys'][$k], $def);
            }
        }

        $options['primary'] = $primary;

        return array('tableName' => $this->getTableOption('tableName'),
                     'columns'   => $columns,
                     'options'   => array_merge($options, $this->getTableOptions()));
    }

    /**
     * Checks whether a persistent field is inherited from a superclass.
     *
     * @return boolean TRUE if the field is inherited, FALSE otherwise.
     */
    public function isInheritedField($fieldName)
    {
        return isset($this->_fieldMappings[$fieldName]['inherited']);
    }

    /**
     * Sets the name of the primary table the class is mapped to.
     *
     * @param string $tableName  The table name.
     */
    public function setTableName($tableName)
    {
        $this->setTableOption('tableName', $this->_em->getConnection()
                ->getFormatter()->getTableName($tableName));
    }

    /**
     * Serializes the metadata.
     *
     * Part of the implementation of the Serializable interface.
     *
     * @return string  The serialized metadata.
     */
    public function serialize()
    {
        //$contents = get_object_vars($this);
        /* @TODO How to handle $this->_em and $this->_parser ? */
        //return serialize($contents);
        return "";
    }

    /**
     * Reconstructs the metadata class from it's serialized representation.
     *
     * Part of the implementation of the Serializable interface.
     *
     * @param string $serialized  The serialized metadata class.
     */
    public function unserialize($serialized)
    {
        return true;
    }

    /**
     * Adds a one-to-one association mapping.
     * 
     * @todo Implementation.
     */
    public function mapOneToOne(array $mapping)
    {
        $oneToOneMapping = new Doctrine_Association_OneToOne($mapping);
        if (isset($this->_associationMappings[$oneToOneMapping->getSourceFieldName()])) {
            throw Doctrine_MappingException::duplicateFieldMapping();
        }
        $this->_associationMappings[$oneToOneMapping->getSourceFieldName()] = $oneToOneMapping;
    }

    /**
     * @todo Implementation.
     */
    public function mapOneToMany(array $mapping)
    {

    }

    /**
     * @todo Implementation.
     */
    public function mapManyToOne(array $mapping)
    {
        
    }

    /**
     * @todo Implementation.
     */
    public function mapManyToMany(array $mapping)
    {

    }

    /**
     * actAs
     * loads the given plugin
     *
     * @param mixed $tpl
     * @param array $options
     * @todo Unify under 'Behaviors'.
     */
    /*public function actAs($tpl, array $options = array())
    {
        if ( ! is_object($tpl)) {
            if (class_exists($tpl, true)) {
                $tpl = new $tpl($options);
            } else {
                $className = 'Doctrine_Template_' . $tpl;
                if ( ! class_exists($className, true)) {
                    throw new Doctrine_Record_Exception("Couldn't load plugin.");
                }
                $tpl = new $className($options);
            }
        }

        if ( ! ($tpl instanceof Doctrine_Template)) {
            throw new Doctrine_Record_Exception('Loaded plugin class is not an instance of Doctrine_Template.');
        }

        $className = get_class($tpl);

        $this->addBehavior($className, $tpl);

        $tpl->setTable($this);
        $tpl->setUp();
        $tpl->setTableDefinition();

        return $this;
    }*/

    /**
     * check
     * adds a check constraint
     *
     * @param mixed $constraint     either a SQL constraint portion or an array of CHECK constraints
     * @param string $name          optional constraint name
     * @return Doctrine_Entity      this object
     * @todo Should be done through $_tableOptions
     * @deprecated
     */
    /*public function check($constraint, $name = null)
    {
        if (is_array($constraint)) {
            foreach ($constraint as $name => $def) {
                $this->_addCheckConstraint($def, $name);
            }
        } else {
            $this->_addCheckConstraint($constraint, $name);
        }

        return $this;
    }
    protected function _addCheckConstraint($definition, $name)
    {
        if (is_string($name)) {
            $this->_tableOptions['checks'][$name] = $definition;
        } else {
            $this->_tableOptions['checks'][] = $definition;
        }
    }*/
    
    /**
     * Registers a custom mapper for the entity class.
     *
     * @param string $mapperClassName  The class name of the custom mapper.
     * @deprecated
     */
    public function setCustomRepositoryClass($repositoryClassName)
    {
        if ( ! is_subclass_of($repositoryClassName, 'Doctrine_EntityRepository')) {
            throw new Doctrine_ClassMetadata_Exception("The custom repository must be a subclass"
                    . " of Doctrine_EntityRepository.");
        }
        $this->_customRepositoryClassName = $repositoryClassName;
    }
    
    /**
     * Gets the name of the custom repository class used for the entity class.
     *
     * @return string|null  The name of the custom repository class or NULL if the entity
     *                      class does not have a custom repository class.
     */
    public function getCustomRepositoryClass()
    {
         return $this->_customRepositoryClassName;
    }

    /**
     * @todo Thoughts & Implementation.
     */
    public function setEntityType($type)
    {
        //Entity::TYPE_ENTITY
        //Entity::TYPE_MAPPED_SUPERCLASS
        //Entity::TYPE_TRANSIENT
    }

    /**
     * Binds the entity instance of this class to a specific EntityManager.
     * 
     * @todo Implementation. Replaces the bindComponent() methods on the old Doctrine_Manager.
     *       Binding an Entity to a specific EntityManager in 2.0 is the same as binding
     *       it to a Connection in 1.0.
     */
    public function bindToEntityManager($emName)
    {

    }
    
    /**
     * Dispatches the lifecycle event of the given Entity to the registered
     * lifecycle callbacks and lifecycle listeners.
     *
     * @param string $event  The lifecycle event.
     * @param Entity $entity  The Entity on which the event occured.
     */
    public function invokeLifecycleCallbacks($lifecycleEvent, Doctrine_Entity $entity)
    {
        foreach ($this->getLifecycleCallbacks($lifecycleEvent) as $callback) {
            $entity->$callback();
        }
        foreach ($this->getLifecycleListeners($lifecycleEvent) as $className => $callback) {
            if ( ! isset($this->_lifecycleListenerInstances[$className])) {
                $this->_lifecycleListenerInstances[$className] = new $className;
            }
            $this->_lifecycleListenerInstances[$className]->$callback($entity);
        }
    }
    
    /**
     * Gets the registered lifecycle callbacks for an event.
     *
     * @param string $event
     * @return array
     */
    public function getLifecycleCallbacks($event)
    {
        return isset($this->_lifecycleCallbacks[$event]) ?
                $this->_lifecycleCallbacks[$event] : array();
    }
    
    /**
     * Gets the registered lifecycle listeners for an event.
     *
     * @param string $event
     * @return array
     */
    public function getLifecycleListeners($event)
    {
        return isset($this->_lifecycleListeners[$event]) ?
                $this->_lifecycleListeners[$event] : array();
    }
    
    /**
     * Adds a lifecycle listener for Entities this class.
     * 
     * Note: If the same listener class is registered more than once, the old
     * one will be overridden.
     *
     * @param string $listenerClass
     * @param array $callbacks
     */
    public function addLifecycleListener($listenerClass, array $callbacks)
    {
        $this->_lifecycleListeners[$event][$listenerClass] = array();
        foreach ($callbacks as $method => $event) {
            $this->_lifecycleListeners[$event][$listenerClass][] = $method;
        }
    }
    
    /**
     * Adds a lifecycle callback for Entities of this class.
     *
     * Note: If the same callback is registered more than once, the old one
     * will be overridden.
     * 
     * @param string $callback
     * @param string $event
     */
    public function addLifecycleCallback($callback, $event)
    {
        if ( ! isset($this->_lifecycleCallbacks[$event])) {
            $this->_lifecycleCallbacks[$event] = array();
        }
        if ( ! in_array($callback, $this->_lifecycleCallbacks[$event])) {
            $this->_lifecycleCallbacks[$event][$callback] = $callback;
        } 
    }

    /**
     * @todo Implementation. Immutable entities can not be updated or deleted once
     *       they are created. This means the entity can only be modified as long as it's
     *       in transient state (TCLEAN, TDIRTY).
     */
    public function isImmutable()
    {
        return false;
    }

    public function isDiscriminatorColumn($columnName)
    {
        return $columnName === $this->_inheritanceOptions['discriminatorColumn'];
    }

    /**
     * hasOne
     * binds One-to-One aggregate relation
     *
     * @param string $componentName     the name of the related component
     * @param string $options           relation options
     * @see Doctrine_Relation::_$definition
     * @return Doctrine_Entity          this object
     */
    public function hasOne()
    {
        $this->bind(func_get_args(), Doctrine_Relation::ONE_AGGREGATE);

        return $this;
    }

    /**
     * hasMany
     * binds One-to-Many / Many-to-Many aggregate relation
     *
     * @param string $componentName     the name of the related component
     * @param string $options           relation options
     * @see Doctrine_Relation::_$definition
     * @return Doctrine_Entity          this object
     */
    public function hasMany()
    {
        $this->bind(func_get_args(), Doctrine_Relation::MANY_AGGREGATE);

        return $this;
    }

    public function hasAttribute($name)
    {
        return isset($this->_attributes[$name]);
    }
    
    public function getAttribute($name)
    {
        if ($this->hasAttribute($name)) {
            return $this->_attributes[$name];
        }
    }
    
    public function setAttribute($name, $value)
    {
        if ($this->hasAttribute($name)) {
            $this->_attributes[$name] = $value;
        }
    }
    
    /* The following stuff needs to be touched for the association mapping rewrite */
    
    public function bindRelation($args, $type)
    {
        return $this->bind($args, $type);
    }

    /**
     * @todo Relation mapping rewrite.
     */
    public function bind($args, $type)
    {
        $options = array();
        $options['type'] = $type;

        if ( ! isset($args[1])) {
            $args[1] = array();
        }
        if ( ! is_array($args[1])) {
            try {
                throw new Exception();
            } catch (Exception $e) {
                echo $e->getTraceAsString();
            }
        }
        $options = array_merge($args[1], $options);
        $this->_parser->bind($args[0], $options);
    }

    /**
     * hasRelation
     *
     * @param string $alias      the relation to check if exists
     * @return boolean           true if the relation exists otherwise false
     */
    public function hasRelation($alias)
    {
        return $this->_parser->hasRelation($alias);
    }

    /**
     * getRelation
     *
     * @param string $alias      relation alias
     */
    public function getRelation($alias, $recursive = true)
    {
        return $this->_parser->getRelation($alias, $recursive);
    }

    public function getRelationParser()
    {
        return $this->_parser;
    }

    /**
     * getRelations
     * returns an array containing all relation objects
     *
     * @return array        an array of Doctrine_Relation objects
     */
    public function getRelations()
    {
        return $this->_parser->getRelations();
    }
    
    /**
     * @todo Set by the factory if type is AUTO. Not pretty. Find sth. better.
     */
    public function setIdGeneratorType($type)
    {
        $this->_generatorType = $type;
    }

    /**
     *
     */
    public function __toString()
    {
        return spl_object_hash($this);
    }
}

