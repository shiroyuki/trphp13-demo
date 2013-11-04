# Understanding Doctrine (2)

(Draft, updated on 2013.10.16)

## Description

This talk briefly introduces the object-oriented mapping (ORM), how Doctrine ORM is designed. Then, this talk will move on to discuss about common misunderstanding about Doctrine, why we should care about Doctrine, how to use it properly and when to use its ORM part or DBAL (Database Abstract Layer).

## Introduction

### What is object relational mapping? (Wikipedia, pattern designs)

ORM in computer software is a programming technique for converting data between incompatible type systems in object-oriented programming languages. This creates, in effect, a "virtual object database" that can be used from within the programming language. There are both free and commercial packages available that perform object-relational mapping, although some programmers opt to create their own ORM tools. (Wikipedia)

### How Doctrine is designed?

The design of Doctrine 2 is initially based on Hibernate, one of Java ORMs, which emphasizes on the concept of dependency injection, **unit of work** and plain old PHP objects (in Java, these are called POJO). There are many design patterns used in Doctrine 2. Since we don't have much time, we will explain how Doctrine 2 applies **object-relational impedance mismatch**, **Data Mapper Pattern** and **Unit of Work**, **Repository Pattern**. (Optional: , Identity Mapper Pattern, Class Metadata / Metadata Mapping Pattern, Hydration - converting a flat-data structure to an object graph)

#### Object-relational Impedance Mismatch



#### Data Mapper Pattern

Doctrine 2 uses the data mapper pattern in the data access layer to move data between objects and a database while keeping them independent of each other and the mapper itself.

#### Unit of Work Pattern

Doctrine 2 uses the unit of work pattern to maintain the list of objects affected by business transaction and coordinate the writing out of changes and the resolution of concurrency problems.

#### Repository Pattern

Doctrine 2 uses the repository pattern to separate the logic that retrieves the data and maps it to the entity model from the business logic that acts on the model. The repository mediates between the data source layer and the business layers of the application.

## Talked Sub-topics

### Change Tracking Policy

The change tracking is the process of determining what has changed in managed entities since the last time they were synchronized with the database.

Doctrine provides 3 different change tracking policies, each having its particular advantages and disadvantages. The change tracking policy can be defined on a per-class basis (or more precisely, per-hierarchy). (per documentation)

By default, Doctrine uses **the implicit change tracking policy** which tells the UnitOfWork to persist every entities reachable by UnitOfWork (DDC-2703). It is pretty convenient for a small app as you can persist everything by just call the "flush" method from the entity manager.

The **explicit change tracking policy** is different from the implicit one that Doctrine 2 only considers entities that have been explicitly marked for change detection through a call to EntityManager's "persist" method or through a save cascade. This policy therefore gives improved performance for larger units of work while sacrificing the behavior of “automatic dirty checking”.

The **notify** tracking policy is similar to explicit except that the developers can have full control to notify the ORM which properties need to be persisted. (This will be refered to the documentation only.)

- Case 1: Use the default policy (persist by reachability) and exhibits that entities that are changed but not persisted are persisted.
- Case 2: Use the explicit policy (persist by demand) and exhibits that entities that are changed but not persisted are not persisted.

### Lazy loading: when to be lazy and not to be.

ORM is a great tool to map and bridge between a native object and the raw data. It is good for agile development, code maintainability and also prototyping. However, Doctrine ORM, pretty much like other ORMs in PHP, suffers from the memory leak in PDO. (Fixed in 5.5?)

Suppose reading Symfony’s documentation or any online introductions of Doctrine is the way you learn how to use Doctrine. There will be something that most people do not know. First of all, all entities in the result set are proxies of the actual entities. One entities may have many proxies. By default, any entities associated to other entities are proxies.

What does this mean?

Suppose you have m entities, named a1, a2, ... and am. During the course of code execution, we make a lot of queries. Each query makes at least one proxy per entity in the result set. Hence, the number of proxies will be around **cm** proxies.

Beside retrieving the ID of the proxy, retrieving or defining properties of a proxy always triggers the proxy loading if the proxy is set for lazy loading. In this situation, it might lead to making around **cm** queries by the end of the execution.

To fix the problem, we have **four solutions**.

First, we can use EntityManager::getUnitOfWork()::initializeObject() for the collection. (Call this one by one?)

Second, we can use DQL to select entity and joined associations. For example, to select a User and the list of associated groups, we can write DQL like this:

	SELECT u, g FROM User u JOIN u.groups g

And the result will have user entities with loaded group entities.

You can also use `SELECT PARTIAL` if you need just the name of the user to avoid proxy loading.

	SELECT PARTIAL u.{id, name} FROM User u

Or if you need the name of the user and group, then you can write:

	SELECT PARTIAL u.{id, name}, PARTIAL g.{id, name} FROM User u JOIN u.groups g

If none of the previous solution does not satisfy you, you may set the fetching mode to "eager". However, it is not recommended.

Here is the example to illustrate how proxies work.

- Case 3: One entities and 9 proxies exhibit at least 10 queries.

### DBAL or ORM?

As many of you may know, Doctrine is split into 3 different sub-packages: common, DBAL and ORM. Unless you are so adventurous, most of you probably only use the ORM. The ORM is convenient and easy to use. However, the cost of using it is sometimes too high.

The ORM consumes a lot of CPU cycles and memory in order to deal with data mapping, managing the data, associations and object references. The resource consumption is not significant unless you deal with a large data set or object graph.

When the overhead is too much, we can use the hydrate array to reduce the resource used in the object-data mapping process.

While using the ORM is like buying a cup of coffee from a coffee shop, using the DBAL is like brewing a cup of coffee with a coffee maker at home.

DBAL abstracts a lot of common instructions used to talk to the databases. It provides no accommodation to the developers in term of handling data in the object fashion. However, the DBAL uses way less resource than the ORM and the DBAL can do in a way that developers truly wants without the magic from UnitOfWork.

When should you use DBAL? It depends.

Generally, using ORM is a good initial approach. However, in where the efficiency (performance, memory etc.) matters, the DBAL is recommended. 

Why should you use ORM first?

It is more readable and maintainable. Otherwise, the code is optimized but difficult to maintain. Also, the code that uses DBAL is usually either difficult to test or not testable by unit tests. Depending on who do the transcription, transcribing the code using the ORM to the one using the DBAL is generally easier and more maintainable than the other way around.

- Case 4: Process 60,000 entities.

### The Dark Side of Cascading: One good example on the fact that what you can do does not mean you should do it.

Cascading operations are magical. It saves you from writing a cool piece of code that traverses the object graphs for an operation. As how ORMs are designed to reduce that hardship from developers, some cascading operations that are not natively supported by the database software are usually supported by ORMs, such as, cascade on persist.

Those additional supports are handy but come with cost.

To illustrate the problem, suppose an entity called “n” with some associations

// Confirm again: "write-operation cascades are considered only in the implicit tracking policy."

- Case 5: Two similar object graphs with and without cascade respectively exhibit the difference in raw performance.

### Doctrine Event System

Since Doctrine 2, the ORM is designed to use the event-driven architecture, allowing foreign objects (listeners) to work with a specific data set (event data). This means you can write the code to intercept the data at a certain event and can do whatever you want with the data.

This architectural design, in my opinion, allows infinite possibility of how the code can be design. However, Doctrine Event System is a double-edge sword.

Let's call Doctrine Event System "DES".

There are many types of events. However, all events are triggered from UnitOfWork. So, as UnitOfWork is not tied to a specific entity, all managed entities or related information, such as change sets, will be passed along to the listeners.

In this situation, even the listener has a check to selectively listen or ignore entities, the number of events triggered is approximately equivalent to the number of managed entities multiplied by the number of event listeners. It is a lot of unnecessary executions if the number of applicable entities is too low.

The solution is simple. Use Doctrine event listeners sparingly if performance is your primary concern.

#### Notes

event listener: for all entities
entity listener: for one particular entity  
lifecycle event: for one particular entity (the implementation is inside the entity.)

- cannot alter associations on onFlush event listeners.

#### Examples

- Case 6: With 5 entities for each entity class (5 classes, 25 entities in total) and 5 silly listeners, this example shows that each listener is called 25 times. So, the total number of event dispatched is 125.
- Case 6.2 and 6.3 will be illustrating LifeCycle and Entity event listeners.

### Second Level Cache

Reference: https://github.com/doctrine/doctrine2/pull/808

When the client make a query to the database, the query speed can be wary due to many factors, such as, queries, I/O performance, database software, network latency, applicable algorithms and especially the size of the data reachable by the targeted database server. Although many database vendors provide some features to allow the faster performance, those features are only applicable under particular condition, such as, proper configuration, state of data in memory etc.

Better than saying sorry, many people resort an approach of using a lookup table in order to reduce the search time where the size of the lookup table is less than or equal to the size of the reachable data.

Doctrine features a new feature called "second level cache" where applies the idea of using a lookup table.

## More reading

- http://msdn.microsoft.com/en-us/magazine/dd569757.aspx