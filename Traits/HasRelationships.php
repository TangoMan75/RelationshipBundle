<?php

namespace TangoMan\RelationshipBundle\Traits;

use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Inflector\Inflector;

/**
 * @author  Matthias Morin <matthias.morin@gmail.com>
 * @author  Fabrice Lazzarotto <fabrice.lazzarotto@gmail.com>
 * @package TangoMan\RelationshipBundle\Traits
 */
trait HasRelationships
{
    /**
     * @var array
     */
    private static $genericMethods = [
        'set',
        'get',
        'link',
        'unLink',
    ];

    /**
     * @var array
     */
    private static $arrayMethods = [
        'add',
        'remove',
        'has',
    ];

    /**
     * This method allows to avoid Twig error.
     * Warning: This will probably disable `by_reference` access to any object property.
     *
     * @param $name
     *
     * @return bool
     */
    public function __isset($name)
    {
        return false;
    }

    /**
     * Return property type or class name
     *
     * @param $property
     *
     * @return bool|string type or class name; false when property doesn't exist
     */
    public function checkPropertyType($property)
    {
        if (property_exists($this, $property)) {
            if (is_object($this->$property)) {
                return get_class($this->$property);
            } else {
                return gettype($this->$property);
            }
        }

        return false;
    }

    /**
     * @param $method
     * @param $arguments
     *
     * @return mixed|null
     */
    public function __call($method, $arguments)
    {
        $validMethods = implode("|", array_merge(self::$genericMethods, self::$arrayMethods));

        if (!preg_match('/^('.$validMethods.')(\w+)$/', $method, $matches)) {
            $action = '';
            $property = $method;
        } else {
            $property = lcfirst($matches[2]);
            $action = $matches[1];
        }

        // When property not found, tries with plural form
        if (!property_exists($this, $property) && !property_exists(
                $this,
                $property = Inflector::pluralize($property)
            )) {
            throw new \BadMethodCallException(
                'Method: '.$method.', or property: '.$property.' doesn\'t exist in class: '.get_class(
                    $this
                )
            );
        }

        if (is_array($this->$property) || $this->$property instanceof Collection) {
            switch ($action) {
                case 'set':
                    return $this->setMany($property, $arguments[0]);
                    break;
                case 'get':
                    return $this->get($property);
                    break;
                case 'has':
                    return $this->has($property, $arguments[0]);
                    break;
                case 'add':
                    return $this->add($property, $arguments[0]);
                    break;
                case 'remove':
                    return $this->remove($property, $arguments[0]);
                    break;
                case 'link':
                    $this->linkMany($property, $arguments[0]);

                    return null;
                    break;
                case 'unLink':
                    $this->unLinkMany($property, $arguments[0]);

                    return null;
                    break;
                case '': // Twig direct access
                    return $this->get($property);
                    break;
            }
        } else {
            switch ($action) {
                case 'set':
                    return $this->set($property, $arguments[0]);
                    break;
                case 'get':
                    return $this->get($property);
                    break;
                case 'link':
                    $this->linkOne($property, $arguments[0]);

                    return null;
                    break;
                case 'unLink':
                    $this->unLinkOne($property);

                    return null;
                    break;
                case '': // Twig direct access
                    return $this->get($property);
                    break;
            }
        }

        throw new \BadMethodCallException('Method '.$method.' doesn\'t exist in class: '.get_class($this));
    }

    /**
     * @param $property
     * @param $item
     *
     * @return $this
     */
    private function set($property, $item)
    {
        $class = substr(strrchr(get_class($this), '\\'), 1);

        if ($item) {
            $this->__call('link'.ucfirst($property), [$item]);
            $item->{'link'.$class}($this);
        } elseif ($item = $this->{'get'.ucfirst($property)}()) {
            $this->__call('unLink'.ucfirst($property), [$item]);
            $item->{'unLink'.$class}($this);
        }

        return $this;
    }

    /**
     * @param $property
     * @param $items Collection
     *
     * @return $this
     */
    private function setMany($property, $items)
    {
        foreach ($this->$property as $item) {
            if (!$items->contains($item)) {
                $this->remove($property, $item);
            }
        }

        foreach ($items as $item) {
            $this->add($property, $item);
        }

        return $this;
    }

    /**
     * @param $property
     * @param $item
     *
     * @return $this
     */
    private function add($property, $item)
    {
        $this->__call('link'.ucfirst($property), [$item]);
        $class = substr(strrchr(get_class($this), '\\'), 1);
        $item->{'link'.$class}($this);

        return $this;
    }

    /**
     * @param $property
     * @param $item
     *
     * @return $this
     */
    private function remove($property, $item)
    {
        $this->__call('unLink'.ucfirst($property), [$item]);
        $class = substr(strrchr(get_class($this), '\\'), 1);
        $item->{'unLink'.ucfirst($class)}($this);

        return $this;
    }

    /**
     * @param $property
     *
     * @return null|mixed
     */
    private function get($property)
    {
        return $this->$property;
    }

    /**
     * @param $property
     * @param $item
     *
     * @return bool|null
     */
    private function has($property, $item)
    {
        if ($this->$property->contains($item)) {
            return true;
        }

        return false;
    }

    /**
     * @param $property
     * @param $item
     */
    private function linkOne($property, $item)
    {
        if (property_exists($this, $property)) {
            $this->$property = $item;
        }
    }

    /**
     * @param $property
     */
    private function unLinkOne($property)
    {
        if (property_exists($this, $property)) {
            $this->$property = null;
        }
    }

    /**
     * @param $property
     * @param $item
     */
    private function linkMany($property, $item)
    {
        if (!$this->$property->contains($item)) {
            $this->$property[] = $item;
        }
    }

    /**
     * @param $property
     * @param $item
     */
    private function unLinkMany($property, $item)
    {
        $this->$property->removeElement($item);
    }
}
