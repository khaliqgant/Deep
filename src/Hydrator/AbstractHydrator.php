<?php

/**
 * Deep
 *
 * @package      rsanchez\Deep
 * @author       Rob Sanchez <info@robsanchez.com>
 */

namespace rsanchez\Deep\Hydrator;

use rsanchez\Deep\Collection\EntryCollection;
use rsanchez\Deep\Model\AbstractEntity;
use rsanchez\Deep\Model\AbstractProperty;

/**
 * Abstract Hydrator class
 *
 * Hydrators bind custom fields properties to Entry objects
 */
abstract class AbstractHydrator implements HydratorInterface
{
    /**
     * The Entry Collection being hydrated
     * @var \rsanchez\Deep\Collection\EntryCollection
     */
    protected $collection;

    /**
     * The other hydrators
     * @var \rsanchez\Deep\Hydrator\HydratorCollection
     */
    protected $hydrators;

    /**
     * The name of the fieldtype
     * @var string
     */
    protected $fieldtype;

    /**
     * Constructor
     *
     * Set the EntryCollection and load any global elements the hydrator might need
     * @param \rsanchez\Deep\Collection\EntryCollection  $collection
     * @param \rsanchez\Deep\Hydrator\HydratorCollection $hydrators
     * @param string                                     $fieldtype
     */
    public function __construct(EntryCollection $collection, HydratorCollection $hydrators, $fieldtype)
    {
        $this->collection = $collection;
        $this->hydrators = $hydrators;
        $this->fieldtype = $fieldtype;
    }

    /**
     * {@inheritdoc}
     */
    public function preload(array $entryIds)
    {
        // load from external DBs here
    }

    /**
     * {@inheritdoc}
     */
    abstract public function hydrate(AbstractEntity $entity, AbstractProperty $property);
}
