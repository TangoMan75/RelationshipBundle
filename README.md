TangoMan Relationship Bundle
============================

**TangoMan Relationship Bundle** provides magic methods for OneToOne, OneToMany, ManyToOne, ManyToMany, relationships.

Installation
============

Step 1: Download the Bundle
---------------------------

Open a command console, enter your project directory and execute the
following command to download the latest stable version of this bundle:

```bash
$ composer require tangoman/relationship-bundle
```

This command requires you to have Composer installed globally, as explained
in the [installation chapter](https://getcomposer.org/doc/00-intro.md)
of the Composer documentation.

Step 2: Enable the Bundle
-------------------------

Then, enable the bundle by adding it to the list of registered bundles
in the `app/AppKernel.php` file of your project:

```php
<?php
// app/AppKernel.php

// ...
class AppKernel extends Kernel
{
    // ...

    public function registerBundles()
    {
        $bundles = array(
            // ...
            new TangoMan\FrontBundle\TangoManRelationshipBundle(),
        );

        // ...
    }
}
```

Step 3: Update your entities
----------------------------

Use `TangoMan\RelationshipBundle\Traits\HasRelationships` trait inside your entities,
and define properties with appropriate doctrine annotations.

Step 4: Update your database schema
-----------------------------------

Open a command console, enter your project directory and execute the
following command to update your database schema:

```console
$ php bin/console schema:update
```

Usage
=====

Entities
--------

This trait provide magic methods to handle both OWNING and INVERSE side of bidirectional relationships.
 - Both entities must use `HasRelationships` trait.
 - Both entities must define properties with appropriate doctrine annotations.
 - `cascade={"persist"}` annotation is MANDATORY (will allow bidirectional linking between entities).
 - @method annotation will allow for correct autocomplete in your IDE (optional).

OneToOne relationships
----------------------

- `cascade={"remove"}` will avoid orphan `Item` on `Owner` deletion (optional).

```php
<?php
// src\AppBundle\Entity\Owner.php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use TangoMan\RelationshipBundle\Traits\HasRelationships;

/**
 * Class Owner
 * @ORM\Table(name="owner")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\OwnerRepository")
 *
 * @package AppBundle\Entity
 *
 * @method $this setItem(Item $item)
 */
class Owner
{
    use HasRelationships;

    // ...

    /**
     * @var Item
     * @ORM\OneToOne(targetEntity="AppBundle\Entity\Item", inversedBy="owner", cascade={"persist", "remove"})
     */
    private $item;

    // ...
```

```php
<?php
// src\AppBundle\Entity\Item.php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use TangoMan\RelationshipBundle\Traits\HasRelationships;

/**
 * Class Item
 * @ORM\Table(name="item")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\ItemRepository")
 *
 * @package AppBundle\Entity
 *
 * @method $this setOwner(Owner $owner)
 */
class Item
{
    use HasRelationships;

    // ...

    /**
     * @var Owner
     * @ORM\OneToOne(targetEntity="AppBundle\Entity\Owner", mappedBy="item", cascade={"persist"})
     */
    private $owner;

    // ...
```

ManyToMany relationships
------------------------

### Entity properties

- Property must own `@var ArrayCollection`
- Property name MUST use plural form (as it represents several entities)
- `@ORM\OrderBy({"id"="DESC"})` will allow to define custom orderBy when fetching `items` (optional).

```php
<?php
// src\AppBundle\Entity\Owner.php

namespace AppBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use TangoMan\RelationshipBundle\Traits\HasRelationships;

/**
 * Class Owner
 * @ORM\Table(name="owner")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\OwnerRepository")
 *
 * @package AppBundle\Entity
 *
 * @method $this setItems(Items $items)
 */
class Owner
{
    use HasRelationships;

    // ...

    /**
     * @var ArrayCollection
     * @ORM\ManyToMany(targetEntity="AppBundle\Entity\Item", inversedBy="owners", cascade={"persist", "remove"})
     * @ORM\OrderBy({"id"="DESC"})
     */
    private $items = [];

    // ...
```

```php
<?php
// src\AppBundle\Entity\Item.php

namespace AppBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use TangoMan\RelationshipBundle\Traits\HasRelationships;

/**
 * Class Item
 * @ORM\Table(name="item")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\ItemRepository")
 *
 * @package AppBundle\Entity
 *
 * @method $this setOwners(Owners $owners)
 */
class Item
{
    use HasRelationships;

    // ...

    /**
     * @var ArrayCollection
     * @ORM\ManyToMany(targetEntity="AppBundle\Entity\Owner", mappedBy="items", cascade={"persist"})
     * @ORM\OrderBy({"id"="DESC"})
     */
    private $owners = [];

    // ...
```

### Entity constructor

Constructors MUST initialize properties with `ArrayCollection`

```php
<?php
// src\AppBundle\Entity\Owner.php

namespace AppBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use TangoMan\RelationshipBundle\Traits\HasRelationships;

// ...

    /**
     * Owner constructor.
     */
    public function __construct()
    {
        // ...
        $this->Items = new ArrayCollection();
    }
```

### FormTypes

Your formTypes elements from the **INVERSE** side of relationships **MUST** own `'by_reference' => false,` attribute 
to force use of appropriate setters and getters (i.e. `add` and `remove` methods).

```php
<?php
// src\AppBundle\Form\ItemType.php
// ...

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            // ...
            ->add(
                'owner',
                EntityType::class,
                [
                    'label'         => 'Owner',
                    'placeholder'   => 'Select owner',
                    'class'         => 'AppBundle:Owner',
                    'by_reference'  => false,
                    'multiple'      => true,
                    'expanded'      => false,
                    'required'      => false,
                    'query_builder' => function (EntityRepository $em) {
                        return $em->createQueryBuilder('o')
                            ->orderBy('o.name');
                    },
                ]
            );
    }
```

Note
====

If you find any bug please report here : [Issues](https://github.com/TangoMan75/RelationshipBundle/issues/new)

License
=======

Copyrights (c) 2017 Matthias Morin

[![License][license-MIT]][license-url]
Distributed under the MIT license.

If you like **TangoMan Relationship Bundle** please star!
And follow me on GitHub: [TangoMan75](https://github.com/TangoMan75)
... And check my other cool projects.

[Matthias Morin | LinkedIn](https://www.linkedin.com/in/morinmatthias)

[license-GPL]: https://img.shields.io/badge/Licence-GPLv3.0-green.svg
[license-MIT]: https://img.shields.io/badge/Licence-MIT-green.svg
[license-url]: LICENSE
