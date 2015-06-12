<?php
namespace Burzum\Search\Search;

use Cake\ORM\Table;
use Burzum\Search\Search\Filter;

class Manager
{

    /**
     * Table
     *
     * @var Table Instance
     */
    protected $_table;

    /**
     * Filter collection and their filters
     *
     * @var array
     */
    protected $_filters = [
        'default' => []
    ];

    /**
     * Active filter collection.
     *
     * @var string
     */
    protected $_collection = 'default';

    /**
     * Config
     *
     * @var array
     */
    protected $_config = [];

    /**
     * Constructor
     *
     * @param Table $table Table
     */
    public function __construct(Table $table)
    {
        $this->_table = $table;
    }

    /**
     * Return all configured types.
     *
     * @return array Config
     */
    public function all()
    {
        return $this->_filters['default'];
    }

    /**
     * Return Table
     *
     * @return Table Table Instance
     */
    public function table()
    {
        return $this->_table;
    }

    /**
     * Gets all filters in a given collection.
     *
     * @param string $collection Name of the filter collection.
     * @return array Array of filter instances.
     */
    public function getFilters($collection = 'default')
    {
        return $this->_filters[$collection];
    }

    /**
     * Sets or gets the filter collection name.
     *
     * @param string $name Name of the active filter collection to set.
     * @return mixed Returns $this or the name of the active collection if no $name was provided.
     */
    public function collection($name = null)
    {
        if ($name === null) {
            return $this->_collection;
        }
        if (!isset($this->_filters[$name])) {
            $this->_filters[$name] = [];
        }
        $this->_collection = $name;
        return $this;
    }

    /**
     * Adds a new filter to the active collection.
     *
     * @param string $name
     * @param string $filter
     * @param array $options
     * @return $this
     */
    public function add($name, $filter, array $options = [])
    {
        $this->_filters[$this->_collection][$name] = $this->_loadFilter($name, $filter, $options);
        return $this;
    }

    /**
     * Removes filter from the active collection.
     *
     * @param string $name Name of the filter to be removed.
     * @return void
     */
    public function remove($name)
    {
        unset($this->_filters[$this->_collection][$name]);
    }

    /**
     * Loads a search filter instance.
     *
     * @param string $name Name of the field
     * @param string $filter Filter name
     * @param array $options Filter options.
     * @return \Burzum\Search\Search\Filter\Base
     * @throws \InvalidArgumentException When no filter was found.
     */
    public function _loadFilter($name, $filter, array $options = [])
    {
        list($plugin, $filter) = pluginSplit($filter);
        if (!empty($plugin)) {
            $className = '\\' . $plugin . '\Search\Type\\' . $filter;
            if (class_exists($className)) {
                return new $className($name, $options, $this);
            }
        }
        if (isset($config['typeClasses'][$filter])) {
            return new $config['typeClasses'][$filter]($filter, $options, $this);
        }
        if (class_exists('\Burzum\Search\Search\Filter\\' . $filter)) {
            $className = '\Burzum\Search\Search\Filter\\' . $filter;
            return new $className($name, $options, $this);
        }
        if (class_exists('\App\Search\Type\\' . $filter)) {
            $className = '\App\Search\Type\\' . $filter;
            return new $className($name, $options, $this);
        }
        throw new \InvalidArgumentException(sprintf('Can\'t find filter class %s!', $name));
    }
}