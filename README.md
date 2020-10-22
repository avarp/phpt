# Strong types in PHP

Since PHP 7.0.0 there is possibility to turn on type checking with `declare(strict_types=1);` statement. So lets use it on 110%.

### Motivation

We used to think of PHP variables as boxes where you can put anything. Does it mean that there is NO types in PHP at all? Of course, not. Types are imminent part of any programming language.

There always was kinda magic we calling type casting. But is this magic black or white? From my experience I can say that about 80% of bugs I ever fixed in PHP code was related to types.

Fortunately, we have type checking in PHP and it prevents us from making stupid type errors. If you still don't understand why types are important, just imageine that you have a program encountered a type error. Who will report you about this error? Angry customer? In case of type checking that kind of code will never leak to production - you even couldn't launch it (you'll get a TypeError).

Functional languages have bunch of good types like Maybe, Either and so on. Inspired by Haskell and Elm this package provides a platform for creating strong typed and immutable data structures

PHP offers set of pre-defined types such as `int`, `bool` or `string`. With type-hinting you can also use class name as a type. Phpt library provides set of abstract classes, which can be used as a basis for your own types.

### Type

Class `Phpt\Types\Type` can be used for cteating simple types. Lets create type describing point in 3D space:

```php
use Phpt\Types\Type;
class Point3D extends Type
{
  static $type = ['float', 'float', 'float'];
}
```

And now we can create a value of that type:

```php
$point = new Point3D([0.1, 0.2, 0.3]);
```

Constructor automaticly checks that value you gave has correct type. In this case it expects a [Tuple](https://en.wikipedia.org/wiki/Tuple) of three floats. If you'll give something else you'll get an exception.

Values are immutable, so there is no ways to modify inner structure of instance. Only one thing you can do is to create a new instance. For example lets create function `translate` which will take 3 floats: offsets by x, y and z axes.

```php
function translate(Point3D $point, float $dx, float $dy, float $dz): Point3D
{
  $coords = $point->getValue(); // Get encountered coordinates
  $newCoords = [$coords[0] + $dx, $coords[1] + $dy, $coords[2] + $dz]; // Create translated coordinates
  return new Point3D($newCoords); // Return new instance
}
```

### Type signature

In previous example creating new type requires from us to implement `type()` function which should return _type signature_.

Type signature describes a type. It can be:

**Scalar type.** It can be trivial, any from: `'int'`, `'float'`, `'string'` and `'bool'`. Or it can be any existing class name. In this case it declares that value should be an instance of given class.

**Complex type.** Can be: List of elements (all elements should be same type), Tuple and Record (or associative arrays). List is represented as `[*]`, Tuple - as `[*, *, ...]` and Record - as `['key1' => *, 'key2' => *, ...]`, where ***** is either scalar or complex type.

### Enum

Class `Phpt\Types\Enum` can be used for cteating enumerations. Lets create type describing state of traffic light:

```php
use Phpt\Types\Enum;
class TrafficLight extends Enum
{
  static $variants = ['Red', 'Yellow', 'Green'];
}
```

Value constructing:

```php
$t = new TrafficLight('Green');
```

Value checking:

```php
if ($t->isRed()) {
  // Wait...
}
if ($t->isYellow()) {
  // Stop!
}
if ($t->isGreen()) {
  // Go!
}
```

### Maybe

Class `Phpt\Types\Maybe` is useful when some function _may_ return a value or it can just fail. Lets assume that we have users of our app represented by model `User`. Assume that class `User` has static method `find` which searches a user by his email and returns an instance of `User` if there is such user.

```php
class User
{
  public static function find(string $email): // Which type we should use here?
  {
    // do searching
  } 
}
```

As you can see, it is not obvious to detect the type of that function. We can't say that it returns `User`. Sometimes, yes, it returns. But in case of failure PHP functions usually returns something weird like `null`, `false` of even `-1`.

For that case functional approach offers type `Maybe` . Of course it is abstract type and in our case it will be something like `MaybeUser`. We can define it in that way:

```php
use Phpt\Types\Maybe;
class MaybeUser extends Maybe
{
  static $a = User::class;
}
```

Stop! What's the heck is this `static $a`? It is just _type variable_. Lets check out how class Maybe is defined in the library:

```php
abstract class Maybe extends Variants
{
  static $variants = [
    'Just' => ['a'],   // <----- a is used here!, but a is not a type!
    'Nothing' => []
  ];
}
```

By default Phpt library provides 8 possible _type variables_ from `a` to `h` and that variables should be defined in child class as static property with the same name.

And now we can define our `find` method:

```php
class User
{
  public static function find(string $email): MaybeUser
  {
    // do search and put it into $user if found
    if (isset($user)) {
      return new MaybeUser('Just', $user);
    } else {
      return new MaybeUser('Nothing');
    }
  } 
}
```

As you can see our result has defined type. And `MaybeUser` looks like `Enum` from previous section. Yes, it behaves in the same way: there are only two variants `'Just'` and `'Nothing'`. But in case `'Just'` you can pass an additional payload.

Lets see how to use our `find` method:

```php
$result = User::find('test@example.com');

if ($result->isNothing()) {
  // No user found
} else {
  $user = $result->getJust();
  // Do something with found result
}
```

No need to guess what function returns in case of failure: null, false or even empty array! And no need to guess which operator we should use `==` or `===` or even `====` to be more confident? (It's just a joke)

### Variants

Class `Phpt\Types\Variants` is kind of enum but with payload. You can think of it as a generalization of `Maybe`. `Maybe` has only two variants, and only one with payload, but with `Variants` you can construct almost any type!

Lets take previous example, but create a function `findByName` which can return: 1) User with exactly given name or 2) User which has only first or last name matched or 3) Nothing.

Here we need more than `Maybe`. Lets create type `NameSearchResult`:

```php
use Phpt\Types\Variants;
class NameSearchResult extends Variants
{
  static $variants = [
    'Exact' => [User::class],
    'Partial' => [User::class],
    'Nothing' => []
  ];
}
```

So, our method `findByName` will return `NameSearchResult` instance. And again we can use "maching" for dealing with this result:

```php
$result = User::findByName('Vasily Petroff');

if ($result->isNothing()) {
  // No user found
}
elseif ($result->isPartial()) {
  $user = $result->getPartial();
  // Do something with found result
}
else {
  $user = $result->getExact();
  // Do something with found result
}
```



