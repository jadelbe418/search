<?php
namespace Search;

use Cake\Core\App;
use Cake\Datasource\RepositoryInterface;
use Cake\Utility\Inflector;
use InvalidArgumentException;
use Search\Model\Filter\FilterCollection;
use Search\Model\Filter\FilterCollectionInterface;
use Search\Model\Filter\FilterLocator;
use Search\Model\Filter\FilterLocatorInterface;

/**
 * Search Manager Service Class
 */
class Manager
{

    /**
     * Repository
     *
     * @var \Cake\Datasource\RepositoryInterface Repository instance
     */
    protected $_repository;

    /**
     * Filter collections
     *
     * @var Search\Model\Filter\FilterCollectionInterface[] Filter collections list.
     */
    protected $_collections = [];

    /**
     * Active filter collection.
     *
     * @var string
     */
    protected $_collection = 'default';

    /**
     * Filter Locator
     *
     * @var \Search\Model\Filter\FilterLocatorInterface
     */
    protected $_filterLocator;

    /**
     * Constructor
     *
     * @param \Cake\Datasource\RepositoryInterface $repository Repository
     */
    public function __construct(RepositoryInterface $repository)
    {
        $this->_repository = $repository;
        $this->_filterLocator = new FilterLocator($this);
        $this->_collections['default'] = new FilterCollection($this->_filterLocator);
    }

    /**
     * Sets the filter locator
     *
     * @param \Search\Model\Filter\FilterLocatorInterface $filterLocator Filter Locator
     * @return $this
     */
    public function setFilterLocator(FilterLocatorInterface $filterLocator)
    {
        $this->_filterLocator = $filterLocator;

        return $this;
    }

    /**
     * Return repository instance.
     *
     * @return \Cake\Datasource\RepositoryInterface Repository Instance
     */
    public function getRepository()
    {
        return $this->_repository;
    }

    /**
     * Gets all filters in a given collection.
     *
     * @param string $collection Name of the filter collection.
     * @return array Array of filter instances.
     * @throws \InvalidArgumentException If requested collection is not set.
     */
    public function getFilters($collection = 'default')
    {
        if (!isset($this->_collections[$collection])) {
            $this->_collections[$collection] = $this->_loadCollection($collection);
        }

        if ($this->_collections[$collection] instanceof FilterCollectionInterface) {
            return $this->_collections[$collection]->toArray();
        }

        return $this->_collections[$collection];
    }

    /**
     * Loads a filter collection.
     *
     * @param string $name Collection name.
     * @return \Search\Model\Filter\FilterCollectionInterface
     * @throws \InvalidArgumentException When no filter was found.
     */
    protected function _loadCollection($name)
    {
        $class = Inflector::camelize($name);

        $className = App::className($class, 'Model/Filter', 'Collection');
        if (!$className) {
            throw new InvalidArgumentException(sprintf(
                'The collection class "%sCollection" does not exist',
                $class
            ));
        }

        return new $className($this->_filterLocator);
    }

    /**
     * Sets the filter collection name to use.
     *
     * @param string $name Name of the active filter collection to set.
     * @return $this
     */
    public function useCollection($name)
    {
        if (!isset($this->_collections[$name])) {
            $this->_collections[$name] = new FilterCollection($this->_filterLocator);
        }
        $this->_collection = $name;

        return $this;
    }

    /**
     * Gets the filter collection name in use currently.
     *
     * @return string The name of the active collection.
     */
    public function getCollection()
    {
        return $this->_collection;
    }

    /**
     * Adds a new filter to the active collection.
     *
     * @param string $name Field name.
     * @param string $filter Filter name.
     * @param array $options Filter options.
     * @return $this
     */
    public function add($name, $filter, array $options = [])
    {
        $this->_collections[$this->_collection]->add($name, $filter, $options);

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
        unset($this->_collections[$this->_collection][$name]);
    }

    /**
     * boolean method
     *
     * @param string $name Name
     * @param array $config Config
     * @return $this
     */
    public function boolean($name, array $config = [])
    {
        $this->add($name, 'Search.Boolean', $config);

        return $this;
    }

    /**
     * like method
     *
     * @param string $name Name
     * @param array $config Config
     * @return $this
     */
    public function like($name, array $config = [])
    {
        $this->add($name, 'Search.Like', $config);

        return $this;
    }

    /**
     * value method
     *
     * @param string $name Name
     * @param array $config Config
     * @return $this
     */
    public function value($name, array $config = [])
    {
        $this->add($name, 'Search.Value', $config);

        return $this;
    }

    /**
     * finder method
     *
     * @param string $name Name
     * @param array $config Config
     * @return $this
     */
    public function finder($name, array $config = [])
    {
        $this->add($name, 'Search.Finder', $config);

        return $this;
    }

    /**
     * callback method
     *
     * @param string $name Name
     * @param array $config Config
     * @return $this
     */
    public function callback($name, array $config = [])
    {
        $this->add($name, 'Search.Callback', $config);

        return $this;
    }

    /**
     * compare method
     *
     * @param string $name Name
     * @param array $config Config
     * @return $this
     */
    public function compare($name, array $config = [])
    {
        $this->add($name, 'Search.Compare', $config);

        return $this;
    }

    /**
     * custom method
     *
     * @param string $name Name
     * @param array $config Config
     * @return $this
     */
    public function custom($name, array $config = [])
    {
        $this->add($name, $config['className'], $config);

        return $this;
    }

    /**
     * Magic method to add filters using custom types.
     *
     * @param string $method Method name.
     * @param array $args Arguments.
     * @return $this
     */
    public function __call($method, $args)
    {
        if (!isset($args[1])) {
            $args[1] = [];
        }

        return $this->add($args[0], $method, $args[1]);
    }
}
