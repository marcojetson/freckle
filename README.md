# Freckle

Freckle is a minimalistic Object-Relational-Mapper built on top of Doctrine DBAL.

Freckle is heavily inspired by [Spot2](https://github.com/vlucas/spot2).

[![Build Status](https://travis-ci.org/marcojetson/freckle.svg?branch=master)](https://travis-ci.org/marcojetson/freckle)
[![Code Climate](https://codeclimate.com/github/marcojetson/freckle/badges/gpa.svg)](https://codeclimate.com/github/marcojetson/freckle)

## Table of contents

- [Installation](#installation)
- [Configuration](#configuration)
- [Entities](#entities)
- [Data manipulation](#data-manipulation)
  - [Insert](#insert)
  - [Update](#update)
  - [Delete](#delete)
  - [Retrieval](#retrieval)
    - [Where operators](#where-operators)
- [Relations](#relations)

## Installation

Install with Composer

```shell
composer require marcojetson/freckle
```

## Configuration

You can get a connection through the `Freckle\Manager` class.

```php
$connection = Freckle\Manager::getConnection([
  'driver' => 'pdo_sqlite',
]);
```

## Entities

Entities must extend ```Freckle\Entity``` and implement the ```definition``` method.

```php
/**
 * @method int getId()
 *
 * @method string getTitle()
 * @method setTitle(string $title)
 *
 * @method string getBody()
 * @method setBody(string $body)
 */
class Post extends Freckle\Entity
{
  public static function definition()
  {
  	return [
      'table' => 'post',
      'fields' => [
        'id' => ['integer', 'sequence' => true, 'primary' => true],
        'title' => 'string',
        'body' => 'string',
      ],
	];
  }
}
```

## Data manipulation

Interact with your entities using a mapper. You can get a mapper using the previously created connection.

```php
$postMapper = $connection->mapper(Post::class);
```

### Insert

```php
// create entity and insert
$post1 = $postMapper->entity([
  'title' => 'Hello World',
  'body' => 'My very first post',
]);

$postMapper->insert($entity);

// ...or do it in a single step
$post2 = $postMapper->create([
  'title' => 'Lorem Ipsum',
  'body' => 'My second post',
]);
```

### Update

```php
$post2->setTitle('Lorem ipsum dolor');
$postMapper->update($post2);
```

Not sure if new entity or not? Then use ```Freckle\Mapper::save()```.

### Delete

```php
$postMapper->delete($post2);
```

### Retrieval

Use ```Freckle\Mapper::find()``` to initialize a query

```php
$query = $postMapper->find(['title like' => '% post']);

// queries are lazy, keep attaching parts till ready
$query->not('id', 1)->gte('score', 10);

foreach ($query as $post) {
  echo $post->getName(), PHP_EOL;
}

// or retrieve a single result
$postMapper->find(['id' => 1])->first();
```

#### Where operators

Where operators can be appended to field when using ```Freckle\Query::where()``` or being executed as query methods.

- eq, equals, =
- not, !=
- gt, greaterThan, >
- gte, greaterThanOrEquals, >=
- lt, lessThan, <
- lte, lessThanOrEquals, <=
- like

##### Custom operators

Add your own operators extending ```Freckle\Operator```.

```php
class JsonExists extends Operator
{
  public function __invoke(Query $query, $column, $value = null)
  {
    return 'jsonb_exists(' . $column . ', ' . $query->parameter($value) . ')';
  }
}

Freckle\Operator::add('json_exists', JsonExists::class);

$postMapper->find([
  'properties json_exists' => 'author',
]);

// or use it as a method
$postMapper->find()->json_exists('properties', 'author');
```

## Relations

Relations are just callbacks. Use ```Freckle\Mapper::one()```, ```Freckle\Mapper::many()```, ```Freckle\Mapper::manyThrough()``` to create relations or create your own queries

```php
/**
 * @method int getId()
 *
 * @method string getBody()
 * @method setBody(string $body)
 *
 * @method int getPostId()
 * @method setPostId(int $postId)
 *
 * @method Post getPost()
 */
class Comment extends Freckle\Entity
{
  public static function definition()
  {
  	return [
      'table' => 'comment',
      'fields' => [
        'id' => ['integer', 'sequence' => true, 'primary' => true],
        'body' => 'string',
        'post_id' => 'integer',
      ],
      'relations' => [
        'post' => function (Mapper $mapper, Comment $comment) {
          return $mapper->one(Post::class, ['id' => $comment->getPostId()]);
        },
      ],
	];
  }
}
```
