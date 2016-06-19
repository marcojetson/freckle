# Freckle

Freckle is a minimalistic Object-Relational-Mapper built on top of Doctrine DBAL.

Freckle is heavily inspired by [Spot2](https://github.com/vlucas/spot2).

[![Build Status](https://travis-ci.org/marcojetson/freckle.svg?branch=master)](https://travis-ci.org/marcojetson/freckle)[![Code Climate](https://codeclimate.com/github/marcojetson/freckle/badges/gpa.svg)](https://codeclimate.com/github/marcojetson/freckle)

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

## Data retrieval and manipulation

You interact with your entities using a mapper. You can get a mapper using the previously created connection.

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

Not sure if new entity or not? Then use ```Freckle\Mapper::save```.

### Delete

```php
$postMapper->delete($post2);
```

### Retrieval

Use ```Freckle\Mapper::find``` to initialize a query

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

## Relations

Relations are just callbacks. Use ```Freckle\Mapper::one```, ```Freckle\Mapper::many```, ```Freckle\Mapper::manyThrough``` to create relations or create your own queries

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
      'table' => 'post',
      'fields' => [
        'id' => ['integer', 'sequence' => true, 'primary' => true],
        'body' => 'string',
        'post_id' => 'integer',
      ],
      'relations' => [
        'post' => function (Mapper $mapper, Comment $coment) {
          return $mapper->one(Post::class, ['id' => $comment->getPostId()]);
        },
      ],
	];
  }
}
```