# Understanding Doctrine

(Draft, updated on 2013.10.16)

## Description

This talk briefly introduces the object-oriented mapping (ORM), how Doctrine ORM is designed. Then, this talk will move on to discuss about common misunderstanding about Doctrine, why we should care about Doctrine, how to use it properly and when to use its ORM part or DBAL (Database Abstract Layer).

## Outline

- What is object relational mapping? (Wikipedia, pattern designs)
- How Doctrine is designed? (Dependency injection, aspect-oriented programming, unit of work, plain old PHP objects)

### Change Tracking Policy: how do I track everyone?

By default, Doctrine ORM uses the implicit change tracking policy which tells UnitOfWork to persist every entities reachable by UnitOfWork (DDC-2703).

As you can tell, it is pretty convenience for a small app as you may not have to explicitly persist anything and everything will be persisted on flush.

Suppose there are post-load Doctrine listeners or methods which manipulates entities. If the listeners or methods are triggered, these entities will be persisted.

- Case 1: Use the default policy (persist by reachability) and exhibits that entities that are changed but not persisted are persisted.
- Case 2: Use the explicit policy (persist by demand) and exhibits that entities that are changed but not persisted are not persisted.

### Lazy loading: when to be lazy and not to be.

ORM is a great tool to map and bridge between a native object and the raw data. It is good for agile development, code maintainability and also prototyping. However, Doctrine ORM, pretty much like other ORMs in PHP, suffers from the memory leak in PDO. (Fixed in 5.5?)

Suppose reading Symfony’s documentation or any online introductions of Doctrine is the way you learn how to use Doctrine. There will be something that most people do not know. First of all, all entities in the result set are proxies of the actual entities. One entities may have many proxies. By default, any entities associated to other entities are proxies.

What does this mean?

Suppose you have m entities, named a1, a2, ... and am. During the course of code execution, we make a lot of queries. Each query makes at least one proxy per entity in the result set. Hence, the number of proxies will be around **cm** proxies.

Beside retrieving the ID of the proxy, retrieving or defining properties of a proxy always triggers the proxy loading if the proxy is set for lazy loading. In this situation, it might lead to making around **cm** queries by the end of the execution.

- Case 3: One entities and 9 proxies exhibit at least 10 queries.

### DBAL or ORM?

As many of you may know, Doctrine is split into 3 different sub-packages: common, DBAL and ORM. Unless you are so adventurous, most of you probably only use the ORM. The ORM is convenient and easy to use. However, the cost of using it is sometimes too high.

The ORM consumes a lot of CPU cycles and memory in order to deal with data mapping, managing the data, associations and object references. The resource consumption is not significant unless you deal with a large data set or object graph.

While using the ORM is like buying a cup of coffee from a coffee shop, using the DBAL is like brewing a cup of coffee with a coffee maker at home.

DBAL abstracts a lot of common instructions used to talk to the databases. It provides no accommodation to the developers in term of handling data in the object fashion. However, the DBAL uses way less resource than the ORM and the DBAL can do in a way that developers truly wants without unknown dark magic from UnitOfWork.

When should you use DBAL? It depends.

Generally, using ORM is a good initial approach. However, in where the efficiency (performance, memory etc.) matters, the DBAL is recommended. 

Why should you use ORM first?

It is more readable and maintainable. Otherwise, the code is optimized but difficult to maintain. Also, the code that uses DBAL is usually either difficult to test or not testable by unit tests. Depending on who do the transcription, transcribing the code using the ORM to the one using the DBAL is generally easier and more maintainable than the other way around.

- Case 4: Process and convert 60,000 of simple data rows into and an object graph.

### The Dark Side of Cascading: One good example on the fact that what you can do does not mean you should do it.

Cascading operations are magical. It saves you from writing a cool piece of code that traverses the object graphs for an operation. As how ORMs are designed to reduce that hardship from developers, some cascading operations that are not natively supported by the database software are usually supported by ORMs, such as, cascade on persist.

Those additional supports are handy but come with cost.

To illustrate the problem, suppose an entity called “n” with some associations

- Case 5: Two similar object graphs with and without cascade respectively exhibit the difference in raw performance.

### Doctrine Event Listener: Again and again.

Since Doctrine 2, the ORM is designed to use the event-driven architecture, allowing foreign objects (listeners) to work with a specific data set (event data). This means anyone can have the code to intercept the data at a certain checkpoint and can do whatever he or she wants with the data.

This architectural design, in my opinion, allows infinite possibility of how the code can be design. However, Doctrine Event System is a double-edge sword.

Let's call Doctrine Event System "DES".

DES, by default, provides several checkpoints. However, all checkpoints are triggered from UnitOfWork. So, as UnitOfWork is not tied to a specific entity, all managed entities or related information, such as change sets, will be passed along to the listeners.

In this situation, even the listener has a check to selectively listen or ignore entities, the number of events triggered is approximately equivalent to the number of managed entities multiplied by the number of event listeners. It is a lot of unnecessary executions if the number of applicable entities is too low.

The solution is simple. Use Doctrine event listeners sparingly if performance is your primary concern.

- Case 6: With 5 entities for each entity class (5 classes, 25 entities in total) and 5 silly listeners, this example shows that each listener is called 25 times. So, the total number of event dispatched is 125.

### Second Level Cache

Reference: https://github.com/doctrine/doctrine2/pull/808