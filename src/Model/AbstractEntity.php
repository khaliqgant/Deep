<?php

/**
 * Deep
 *
 * @package      rsanchez\Deep
 * @author       Rob Sanchez <info@robsanchez.com>
 */

namespace rsanchez\Deep\Model;

use rsanchez\Deep\Validation\Factory as ValidatorFactory;
use rsanchez\Deep\Validation\ProvidesValidationRulesInterface;
use rsanchez\Deep\Model\AbstractProperty;
use rsanchez\Deep\Validation\ValidatableInterface;
use DateTime;

/**
 * Model for the channel entries, matrix rows and grid rows
 */
abstract class AbstractEntity extends Model
{
    /**
     * List of custom fields
     *   field_name => mixed value
     *
     * @var array
     */
    protected $customFields = [];

    /**
     * List of regex patterns of attributes to hide from toArray
     * @var array
     */
    protected $hiddenPatterns = [];

    /**
     * Set a custom field attribute
     * @param string $name  field/col/property name
     * @param mixed  $value
     */
    public function setCustomField($name, $value)
    {
        $this->customFields[$name] = $value;
    }

    /**
     * {@inheritdoc}
     *
     * override to set custom field if $name matches a
     * current custom field
     */
    public function setAttribute($name, $value)
    {
        if (array_key_exists($name, $this->customFields)) {
            $this->customFields[$name] = $value;
        } else {
            parent::setAttribute($name, $value);
        }
    }

    /**
     * {@inheritdoc}
     *
     * override to get custom field if $name matches a
     * current custom field
     */
    public function getAttribute($name)
    {
        if (array_key_exists($name, $this->customFields)) {
            return $this->customFields[$name];
        }

        return parent::getAttribute($name);
    }

    /**
     * {@inheritdoc}
     *
     * override to 
     * 1) remove attributes that match $this->hiddenPatterns
     * 2) add custom fields to the resulting array
     */
    public function toArray()
    {
        $array = parent::toArray();

        $self = $this;

        foreach ($array as $key => $value) {
            foreach ($this->hiddenPatterns as $pattern) {
                if (preg_match($pattern, $key)) {
                    unset($array[$key]);
                }
            }
        }

        $this->getProperties()->each(function ($property) use (&$array, $self) {
            $name = $property->getName();

            $value = $self->{$name};

            if (method_exists($value, 'toArray')) {
                $array[$name] = $value->toArray();
            } elseif (method_exists($value, '__toString')) {
                $array[$name] = (string) $value;
            } elseif (is_object($value)) {
                $array[$name] = (array) $value;
            } else {
                $array[$name] = $value;
            }
        });

        return $array;
    }

    /**
     * {@inheritdoc}
     */
    public function getValidatableAttributes()
    {
        $attributes = parent::getValidatableAttributes();

        foreach ($this->getProperties() as $property) {
            $value = $this->{$property->getName()};

            if ($value instanceof ValidatableInterface) {
                $attributes[$property->getIdentifier()] = $value->getValidatableAttributes();
            } elseif ($value instanceof DateTime) {
                $attributes[$property->getIdentifier()] = $value->format('U');
            } else {
                $attributes[$property->getIdentifier()] = $value;
            }
        }

        return $attributes;
    }

    /**
     * {@inheritdoc}
     */
    public function getAttributeNames($prefix = '')
    {
        $names = parent::getAttributeNames($prefix);

        $prefix = $prefix ? rtrim($prefix, '.').'.' : '';

        foreach ($this->getProperties() as $property) {
            $names[$prefix.$property->getIdentifier()] = $property->getLabel();

            $value = $this->{$property->getName()};

            if ($value instanceof ProvidesValidationRulesInterface) {
                $names = array_merge($names, $value->getAttributeNames($prefix.$property->getIdentifier().'.'));
            }
        }

        return $names;
    }

    /**
     * {@inheritdoc}
     */
    public function getValidationRules(ValidatorFactory $validatorFactory, AbstractProperty $parentProperty = null)
    {
        $rules = parent::getValidationRules($validatorFactory, $parentProperty);

        foreach ($this->getProperties() as $property) {
            $value = $this->{$property->getName()};

            $propertyRules = [];

            if ($validatorFactory->hasPropertyValidator($property)) {
                $propertyRules = $validatorFactory->makePropertyValidator($property)->getRules($property);
            }

            if ($property->isRequired()) {
                array_unshift($propertyRules, 'required');
            }

            if ($propertyRules) {
                $rules[$property->getIdentifier()] = $propertyRules;
            }

            if ($value instanceof ProvidesValidationRulesInterface) {
                foreach ($value->getValidationRules($validatorFactory, $property) as $key => $val) {
                    $rules[$property->getIdentifier().'.'.$key] = $val;
                }
            }
        }

        return $rules;
    }

    /**
     * {@inheritdoc}
     *
     * Invoke any invokeable attributes
     */
    public function __call($name, $args)
    {
        if (isset($this->customFields[$name]) && is_callable($this->customFields[$name])) {
            return call_user_func_array($this->customFields[$name], $args);
        }

        return parent::__call($name, $args);
    }

    /**
     * Get the entity ID (eg. entry_id or row_id)
     * @return string|int
     */
    abstract public function getId();

    /**
     * Get the entity type (eg. 'matrix' or 'grid' or 'entry')
     * @return string|null
     */
    abstract public function getType();

    /**
     * Get the entity prefix (eg. 'entry' or 'row')
     * @return string|null
     */
    abstract public function getPrefix();

    /**
     * Get collection of AbstractProperties
     * @return \rsanchez\Deep\Collection\PropertyCollection
     */
    abstract public function getProperties();
}
