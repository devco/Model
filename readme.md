Introduction
------------

### What

Model is a simple, lightweight and easy-to-use Domain Driven Entity framework written using PHP 5.4.x in mind.

### Why

Because you want your models to be defined by your business requirements not database requirements. You also want control over how backends are used to access data whether it be Zend, Doctrine, Propel or simply just PDO or MongoDB. It even leaves you free to use other data sources and libraries; there's really no strings attached.

Theory of Abstraction
---------------------

Since you are not tied to a specific backend, you are free to choose how you structure your entities and repositories without basing it on how it will be stored or retrieved. When you structure your entities, you should think solely about how you will be using them from a domain perspective not how it will be stored in the backend. Mappers can be used to import and export between backends and business entities.

Entities
--------

To create an entity, all you really have to do is extend the base entity class:

    <?php
    
    namespace Model\Entity;
    
    class Content extends Entity
    {
        
    }

### Configuration

Entities allow you to specify an `init()` method for you to place code that sets up the entity or you can use annotations via PHPDoc tags to tell the entity how you want it to be set up.

### Value Objects

Value objects are used to define properties. By default, a range of value objects are provided under the `Model\Vo` namespace but you can also create your own by implementing `Model\Vo\VoInterface` or extending either `Model\Vo\VoAbstract` or `Model\Vo\Generic`. For most cases, simply extending the Generic value object and overriding any methods you need to will suffice. It extends the VoAbstract value object and provides validation facilities on top of your standard setting, getting, checking and removing.

You can apply a value object to an entity 2 different ways. First, you can simply use the `setVo` method most-likely in your `init()` method.

    public function init()
    {
        $this->setVo('name', new Model\Vo\Generic);
    }

Secondly, and probably more commonly, you can apply a value object by annotating the property you want it to act as.

    /**
     * @var Model\Vo\Generic
     */
    public $name = 'Default Value';

If doing it this way, you must make the property public. This way, it can be unset and the magic methods can take over using the value object. The default value given becomes the initial value for the value object.

Some Value objects require arguments passed to them. If this does not need to be dynamic, you can specify them in the annotation. Specify them just as you would an argument list to a method.

    /**
     * @var Model\Vo\VoSet 'Model\Vo\Date', ['format' => 'Y-m-d H:i:s']
     */
    public $updated = 'now';

`Boolean`

Value is cast and maintained as a boolean value. All falsy or truthy values are converetd to `false` and `true`, respectively.

`Date`

Value is passed into and manipulated using a `DateTime` object. The constructor accepts a configuration object so that you can configure the output format and timezone if you want.

    $entity->setVo('updated', new Model\Vo\Date([
        'format'   => 'Y-m-d H:i:s',
        'timezone' => 'Australia/Sydney'
    ]));

    $entity->date = 'yesterday 1 hour ago';

    // 2012-10-12 16:10:55
    $entity->date;

`Enum`

Expects an array of values to restrict the value being set to.

    $entity->setVo('gender', new Model\Vo\Enum([
        'male',
        'female'
    ]));

    $entity->gender = 'male';

`EnumSet`

Expects an array of values to restrict the incoming array to.

    $entity->setVo('guitars', new Model\Vo\EnumSet([
        'Cordoba',
        'Gibson',
        'Ibanez',
        'Parker',
        'Seagull'
    ]));

    $entity->guitars = ['Ibanez', 'Parker'];

`Filter`

Allows an arbitrary `callable` to be used to filter a value before it is set.

    $entity->setVo('forename', new Model\Vo\Filter(function($forename) {
        return ucfirst($forename);
    }));

    $entity->forename = 'trey';

    // Trey
    $entity->forename;

`Float`

Casts any value to a floating point number.

`Generic`

A basic VO that passes through any value. Generall this is used to

`HasMany`

Allows a one-to-many relationship to a given entity set of other entities.

    $entity->setVo('hobbies', new Model\Vo\HasMany('Moden\Entity\Hobby'));

    $entity->hobbies = [[
        'name' => 'Music',
        'type' => 'art'
    ], [
        'name' => 'Golf',
        'type' => 'sport'
    ]];

    // Music
    $entity->hobbies->first()->name;

`HasOne`

Allows a one-to-one relationship to another entity.

    $entity->setVo('address', new Model\Vo\HasOne('Model\Entity\Address'));

    $entity->address = [
        'number'   => '1',
        'street'   => 'Ice Street',
        'city'     => 'Presenton',
        'country'  => 'North Pole',
        'postcode' => '12345'
    ];

    // North Pole
    $entity->address->country;

`Integer`

Ensures the value is an integer.

`Money`

Value is cast as a `string` and `number_format()` is used to format the string to 2 decimal places.

`Set`

Represents a set, or array, of arbitrary values.

`String`

Ensures the passed value is always a string.

`UniqueId`

Extends the generic value object and initialises the value to a unique string.

`VoSet`

Represents an array of value objects. For example, you may want an array of date objects.

    $entity->setVo('edits', new Model\Vo\VoSet('Model\Vo\Date', [
        'format'   => 'd/M/Y',
        'timeozne' => 'Australia/Sydney'
    ]));

Filters
=======

Filters are simple classes which can be assigned to other classes or methods to intercept data before it is accessed by the filters assignment. Filters provide a way of running pre-defined code to translate data or perform other tasks, with routines shared by multiple classes or methods.

Filters are used to intercept data during import or export (when an entity's to() or from() methods are called). filters are primarily used for translating data from one format to another. Translation is usually needed when working with legacy entity data.

A filter's *__invoke()* method signature looks like this:

        public function __invoke(array $data)

All defined filters are located under app/main/src/Model/Filter


Filter Usage
============

Defining a filter
-----------------

Filters are defined by a simple class implementing the *__invoke()* magic method. The name of the class should match the data source or destination i.e. *db* for database. The location of the filter should be specific to the direction of the data the filter is processing, either *to* or *from*.

    e.g. Your file path for a database filter for translating database data to entity data may look something like this:
     app/main/src/Filter/From/Billing/CodeGroup/Db.php

The invoke method on the filter should accept one argument, an array, and return one argument, also an array. The returned array is the filtered data.

Using a filter
--------------

To use a filter with an entity, you may attach it to a class or method by using the docTag argument *@filter*. The pattern to use will match the following:

    @filter from <filter type i.e. db> using <class name i.e. Model\Filter\From\Billing\CodeGroup\Db>

e.g.
        /**
         * Represents a billing item code.
         *
         * @filter from db using Model\Filter\From\Billing\CodeGroup\Db
         */
        class CodeGroup extends Entity
        ...

Using a filter when instantiating an entity should look something like this:

        $found = ServiceContainer::main()->db->find
            ->in('billing.code_groups')
            ->where('icg_uid', $id)
            ->one();

        $entity = new Entity\Billing\CodeGroup($found, 'db');

Notice the second argument in CodeGroup($found, 'db') this argument needs to match the *filter type* in the docTag. e.g. @filter from <filter type>


Gotcha! Not all error situations cause errors
---------------------------------------------

In some situations if a filter is not found, an error will not be thrown. If you suspect that a filter is not being applied, you may need to insert some debug in to the the filter's *__invoke()* method to see if the method is being called. If the invoke method is not being called, then it's possible that either your _$entity = new Entity\Billing\CodeGroup($found, **'db'**);_ filter  argument name is incorrect or your _@filter from **db** using Model\Filter\From\Billing\CodeGroup\Db_ argument is incorrect.


### Validators

Validators are used to validate the state of an entity. The only requirement for a validator is that it `is_callable()`. There are two types of ways to validate your entities. You can either attach a validator to the entity itself or directly to a value object.

Attaching them to your entities are handy when you need to validate the entity's state based on different property values. You may have one value that depends on another.

    $entity->addValidator('That's malarkey! The content ":title" cannot be created before it is updated.', function($entity) {
        return $entity->created <= $entity->updated;
    });

You can also use the `@validator` tag to apply it to an entity

    /**
     * @validator Model\Validator\EnsureCreatedIsBeforeUpdated The content ":title" cannot be created before it is updated.
     */
    class Content extends Model\Entity\Entity
    {

    }

Attaching them to your value objects are great for when you are doing very specific validation on a type of value like whether or not the created date is a valid date.

    $entity->getVo('title')->addValidator('":title" is an invalid content title.', function($vo) {
        return (new Zend\Validator\Alnum)->isValid($vo->get());
    });

Or you can use annotations:

    /**
     * @var Model\Vo\String
     * 
     * @validator Zend\Validator\NotEmpty The user's name must not be empty.
     */
    public $name;

When using annotations for properties, you must ensure that the `@var` value object definition tag is placed before the `@validator` tag or the configurator for validator will complain because it needs the value object in order to apply validators to.

Validators allow anything that is `callable`. Additionally, when using annotations, you can pass a class name, function name as well as a Zend Framework 1.x and 2.x validator class names.

### Behaviors via Traits

You'll notice the use of the `Timestampable` trait. This trait is not included in the library, however, it exemplifies how you can use traits to mix functionality into your entities. We assume the trait has the following definition:

    <?php
    
    namespace Model\Behavior;
    
    trait Timestampable
    {
        /**
         * @var Model\Vo\Datetime
         * 
         * @validator Zend\Validator\Date The created date ":created" is not valid.
         */
        public $created;
        
        /**
         * @var Model\Vo\Datetime
         * 
         * @validator Zend\Validator\Date The last updated date ":updated" is not valid.
         */
        public $updated;
    }

### Relationships

As described earlier, relationships are defined using the `HasOne` and `HasMany` value objects:

    <?php

    namespace Model\Entity;

    class Content extends Entity
    {
        /**
         * @var Model\Vo\HasOne 'Model\Entity\Content\User'
         */
        public $user;
        
        /**
         * The the past modifications of the entity.
         * 
         * @var Model\Vo\HasMany 'Model\Entity\Content\Modification'
         */
        public $modifications;
    }

By adding relationships, you ensure that if the specified property is set or accessed, that it is an instance of the specified class.

    <?php
    
    use Entity\Content;
    
    $entity = new Content;
    
    // instance of Model\Entity\Content\User
    $user = $entity->user;
    
    // instance of Model\EntitySet containing instances of Model\Entity\Content\Modification
    $modifications = $entity->modifications;

This means that if you set an array to one of these properties, it will ensure that an instance of the specified relationship is instantiated and filled with the specified array data.

    $entity->user = array('name' => 'Me');

And you can even pass any traversable item:

    $user       = new stdClass;
    $user->name = 'Me';
    
    // applying a stdClass
    $entity->user = $user;
    
    // entity sets work the same way
    $entity->modifications = array(
        array('name' => 'Me'),
        new stdClass,
    );

### Validating an Entity

When it comes time to validate your entity, you have two options. First, you can simply validate the entity and get it's error messages.
    
    if ($errors = $entity->validate()) {
        // do some error handling
    }

However, when your root entity is not valid, you'll most likely want to halt execution and catch it somewhere. The `assert()` method allows you to do just that.

    // validate and throw an exception if it's not valid
    $entity->assert('Some errors occured.', 1000);

Asserting will throw a special type of exception which is an isntance of `Model\Validator\ValidatorException`. This exception class allows you to get each error message that was caught during the entire validation of the entity. This includes all entity validators and value object validators of the root entity and all child entities.

This allows you to catch that somewhere in your code.

    use Model\Validator\ValidatorException;
    use Exception;

    try {
        // do something like dispatch your application
    } catch (ValidatorException $e) {
        // handle validation errors
    } catch (Exception $e) {
        // fatal exception
    }

You can handle that any way you want using the methods in the exception:

    <?php
    
    use Model\Validator\ValidatorException;
    
    // allows a main message
    $exception = new ValidatorException('The following errors happened:');
    
    // allows you to add messages
    $exception->addMessage('my first message');
    $exception->addMessages([
        'my second message',
        'my third message'
    ]);
    
    // implements IteratorAggregate
    foreach ($exception as $message) {
        ...
    }
    
    // The following errors happened:
    // 
    // - my first message
    // - my second message
    // - my third message
    // 
    // [stack trace goes here]
    echo $exception;
    
    // or you can just throw it
    throw $exception;

Repositories
------------

Authoring repositories is fairly straight forward:

    <?php
    
    namespace Model\Repository;
    use Model\Entity;
    
    class Content
    {
        public function getById($id)
        {
            ...
        }
        
        public function getByTitle($title)
        {
            ...
        }
        
        public function save(Entity\Content $content)
        {
            ...
        }
    }

### Caching

Caching is automated on repository methods. The method name and arguments are used to generate a unique key that is used to publish the return value to a backend of your choice. The next time the method is called, it is pulled from cache, given its lifetime hasn't expired and then returned instead of running the method again.

In order for the caching process to be automated, we must proxy methods through `__call()`. This means that methods must be marked as `protected`.

Multiple different backends are supllied under the `Model\Cache` namespace.

`Memcache`

Probably the most popular option and easy to use. The PHP Memcache PECL [extension](http://php.net/memcache) is used under the hood, so make sure you have it installed.

    $entity->setCacheDriver('Memcache', new Model\Cache\Memcache([
        'servers' => [[
            'host' => 'localhost',
            'port' => 11211
        ]]
    ]));

`Mongo`

MongoDB has been gaining a lot of popularity. It's speed being on-par with memcache and it's flexible structure make it a very good solution for caching. The PHP Mongo PECL [extension](http://php.net/mongo) is used.

    $entity->setCacheDriver('Mongo', new Model\Cache\Mongo([
        'db'         => 'cache',
        'collection' => 'cache',
        'dsn'        => null,
        'lifetime'   => null,
        'options'    => []
    ]));

`Php`

The PHP cache driver simply stores the value in memory for the current script's lifecycle. Currently the lifetime value is ignored.

    $entity->setCacheDriver('PHP', new Model\Cache\Php);

In the examples, cache drivers are applied to entities and given a name inside of the `init()` method. These drivers can now be referenced from other repository methods using annotations. The annotations are given a fluid syntax just as if you were writing a sentence.

    /**
     * @cache Using Memcache for 1 hour.
     */
    protected function getById($id)
    {

    }

That would tell the repository, store that result in Memcache for 1 hour. Afte an hour, the method would be executed again and the cycle would continue.

If you want to store an item in cache for ever, you just omit the part about lifetime.

    /**
     * @cache Using PHP.
     */
    protected function getById($id)
    {

    }

You can combine these also using the `Chain` driver.

    $chain = new Model\Cache\Chain;
    $chain->add(new Model\Cache\Php);
    $chain->add(new Model\Cache\Memcache);
    $entity->setCacheDriver('Php and Memcache', $chain);

Drivers are used in the order in which they are applied, so this would look first in PHP then in Memcache for the result. When persisting to cache, it is also persisted to all drivers in this order.

You would then use this in the same way as before.

    /**
     * @cache Using PHP and Memcache for 1 hour.
     */
    protected function getById($id)
    {

    }

Performance
-----------

One harsh reality we have to deal with as PHP developers is ORMs, while aplenty, are inherently slow. If you are managing large datasets (1000+ entities), you should be careful how you use them if you are expecting a performant result. People go on about performance between Propel, Doctrine, etcetera. You can be as nitpicky as you want, but the performance between all of the tools out there won't make a difference if you are lazy and don't property architect your solution.

### Annotations

Care has been taken in annotations to do as little parsing as necessary. Doc comments on any one class or method are usually only parsed once and the information necessary to configure that entity or repository is kept in memory for the next time we have to configure an entity or repository of the same type.

Real-time annotations are successfully being used in production with little to no performance side-effects. Of course, if you want to do one of those silly benchmarks, go for it.

### Caching

Caching can be very useful. Sometimes it is genuinely necessary and sometimes it is just a bandaid for bad design. Use at your own discretion.

### Background Processes

If you are running CRUD operations on large data sets, it may be a sound idea to run a background process or use a job queue to handle it. This means that you can return a result to the user very quickly while still performing operations on large amounts of data without sacrificing the convenience of an ORM. Let's face it, ORMs are convenient and can really help you manage your code. Why should we sacrifice that if we can help it?

### Dealing with Less Data

In a lot of cases, we are just doing it wrong. We don't need to display 1000 items to the user all at once or update 1000 records while they wait. A lot of times the programmer is the problem, not the tool.

License
-------

Copyright (c) 2005-2013 Trey Shugart

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
