# Strong types in PHP

Since PHP 7.0.0 there is possibility to turn on type checking with `declare(strict_types=1);` statement. So lets use it on 110%.

### Motivation

We used to think of PHP variables as boxes where you can put anything. Does it mean that there is NO types in PHP at all? Of course, not. Types are imminent part of any programming language.

There always was kinda magic we calling type casting. But is this magic black or white? From my experience I can say that about 80% of bugs I ever fixed in PHP code was related to types.

Fortunately, we have type checking in PHP and it prevents us from making stupid type errors. If you still don't understand why types are important, just imageine that you have a program encountered a type error. Who will report you about this error? Angry customer? In case of type checking that kind of code will never leak to production - you even couldn't launch it (you'll get a TypeError).

Functional languages have bunch of good types like Maybe, Either and so on. Inspired by Haskell and Elm this package provides a platform for creating strong typed and immutable data structures

PHP offers set of pre-defined types such as `int`, `bool` or `string`. With type-hinting you can also use class name as a type. Phpt library provides set of abstract classes, which can be used as a basis for your own types.

### Installation

Installation with composer: `composer require artem-vorobev/phpt`

You need to require autoload file. Then all Phpt classes are ready!

```php
require('vendor/autoload.php');
```

 





## Usage



### 1. Typed values



#### 1.1 Value creation

Class `Phpt\Types\Type` can be used for creating both simple values and complex typed arrays. Lets create type describing point in 3D space:

```php
use Phpt\Types\Type;
class Point3D extends Type
{
  static $type = ['float', 'float', 'float'];
}
```

Variable $type is only one required value to be defined. It can be:

- Scalar value
  - Integer, defined by string `'int'`
  - Float, defined by string `'float'`
  - String, defined by string `'string'`
  - Boolean, defined by string `'bool'`
  - Instance of class, defined by class name. Recursive definition is allowed but in this case will cause infinite recursion.
- Complex value
  - List, defined by array with one element `[*]`, where * is either scalar of complex type. All elements in list must have same type.
  - Tuple, defined by array with _n_ elements `[*, ...]`, where _n_ > 1 and * means the same as for lists. Elements of tuple may have different types.
  - Record, defined by associative array `['key1' => *, 'key2' => *, ...]`, Record should have at least one string key. Elements of record may have different types.
- Type variable. Letters from `'a'` to `'h'` are resefved for type variables. So, you can create abstract type with type variables instead of final types and in child classes define those types. See example below:

```php
abstract class ListOf extends Type // This class is actually defined in the library as Phpt\Types\ListOf
{
  static $type = ['a'];
}

class ListOfString extends ListOf
{
  static $a = 'string';
}

class ListOfBools extends ListOf
{
  static $a = 'bool';
}
```



#### 1.2 Using class name as name of type

The library doesn't restrict which classes you can use as the type, but it is strongly recommended to avoid using any classes which are not inherited from classes: `Phpt\Types\Type`, `Phpt\Abstractions\TypedValue`, `Phpt\Types\Variants` and `Phpt\Types\Enum`. Possible caveats here are calling methods which are not defined in your class and possible lost of immutability (see also §4)



#### 1.3 Access to values

Class `Phpt\Types\Type` implements interfaces `ArrayAccess`, `Iterator` and `Countable`, so you can access inner values using syntax described below:

```php
$point = new Point3D([0.1, 0.2, 0.3]);
echo "Point is: $point[0], $point[1], $point[2]";

foreach ($point as $coord) {
  // do something
}

count($point) == 3 // true
```

For records there is also alternative object-oriented syntax available:

```php
class User extends Type
{
  static $type = [
    'id' => 'int',
    'name' => 'string'
  ];
}

$user = new User(['id' => 1, 'name' => 'John']);
echo "Hello, {$user->name}";
```

For scalar values you can retrieve encapsulated value like this:

```php
class Int extends Type
{
  static $type = 'int';
}

$answer = new Int(42);
echo "Answer is, {$answer->value}";
```



#### 1.4 Immutability and mutation

Typed values are immutable and you'll get an error if you'll try to do that:

```php
$point = new Point3D([0.1, 0.2, 0.3]);
$point[0] = 3.141; // this line will cause an error
```

Instead of that you need to create a new instance _with_ updated value.

```php
$newPoint = $point->with([0 => 3.141]);
```

Method `with(array $patch)` accepts an array with values for those keys you want to change. Method `with` works only for complex types. For scalar types use method `withValue($value)` which return a new instance with given value.

Methods `with` also works correctly in case of deeply nested structures:

```php
// Even though the method was called on a nested property it will return whole new patched tree
// But this new tree will share all elements from previous tree except those which were changed
$value1 = $value0->key1->key2->...->with(...);

get_class($value0) == get_class($value1) // true
```



#### 1.5 Operations with lists

There are 5 methods defined for working with lists:

- `pushed(...$elements)` Returns new list with _n_ values pushed to the end
- `popped(int $n=1)` Returns new list with _n_ values dropped from the end
- `shifted(int $n=1)` Returns new list with _n_ values pushed to the start
- `unshifted(...$elements)` Returns new list with _n_ values dropped from the start
- `spliced(int $offset, int $length, array $replacement=[])` Returns new list "spliced" by function `array_splice`

Like method `with` those functions work correctly also for deeply nested structures



#### 1.6 Checking equality

There is method `equal($value)` which takes value you wan compare with. Two typed values are equal if:

1. They both are instances of the same class, i.e both have the same type
2. They have equal content





### 2. Mixed types

If you already know any functional language like Haskell or Scala, you already know the concepts. Anyway mixed types is powerful instrument when you're working with values which sometimes have one type and sometimes another.



#### 2.1 Maybe

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

For that case you can use type `Maybe` . Of course it is abstract type and in our case it will be something like `MaybeUser`. We can define it in that way:

```php
use Phpt\Types\Maybe;
class MaybeUser extends Maybe
{
  static $a = User::class;
}
```

As you can see, we defined it using type variable. And now we can define our `find` method:

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

As you can see our result has defined type. And there are only two variants `'Just'` and `'Nothing'`. In case `'Just'` you can pass an additional payload. Lets see how to use our `find` method:

```php
$result = User::find('test@example.com');

if ($result->isNothing()) {
  // No user found
} else {
  $user = $result->getJust();
  // Do something with found result
}
```

No need to guess what function returns in case of failure: null, false or even empty array! And no need to guess which operator we should use `==` or `===` or even `====` to be more confident?



#### 2.2 Variants

As you may notice class `MaybeUser` was defined using type variable. Lets check out how class Maybe is defined in the library:

```php
abstract class Maybe extends Variants
{
  static $variants = [
    'Just' => ['a'],   // type variable
    'Nothing' => []
  ];
}
```

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



#### 2.3 Formal description of Variants

Any instance of `Phpt\Types\Variants` have _n_ _type constructors_. Type constructor is just string representing variant. It is recommended to use capitalized form for type constructor.

Each type constructor may have a *payload*. Payload is one or more typed values.

If type constructor has a payload there are two methods available `is*` and `get*`, where * is name of constructor. Method `is*` return true if value was created with given constructor. Method `get*` returns payload as array of typed values. If there is only one value in payload it will be returned directly (without wrapping into array).

If type doesn't have a payload only method `is*` is available.



#### 2.4 Imutability and mutation

Variants are immutable. There is no way to change anything.



#### 2.5 Recursion

You can define mixed type recursively. It is useful for such of structures like trees and linked lists. There is an example of binary tree of integer values below:

```php
class Tree extends Variants
{
  static $variants = [
    'Empty' => [],
    'Node' => ['int', Tree::class, Tree::class]
  ];
}
```



#### 2.6 Checking equality

There is method `equal($value)` . It works in the same way as method of class `Type` (see §1.6).





### 3. Enum

Class `Phpt\Types\Enum` can be used for cteating enumerations. Lets create type describing state of traffic light:

```php
use Phpt\Types\Enum;
class TrafficLight extends Enum
{
  static $variants = ['Red', 'Yellow', 'Green'];
}
```



#### 3.1 Value creation

```php
$t = new TrafficLight('Green');
```



#### 3.2 Value matching

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



#### 3.4 Imutability and mutation

Enum instances are immutable. There is no way to change it.



#### 3.5 Checking equality

There is method `equal($value)` . It works in the same way as method of class `Type` (see §1.6).





### 4. Internal representation

All three classes: `Type`,  `Enum` and `Variants` are based on the same idea: ther is internal value and methods controlling it. With special methods you can get this internal representation. Internal representation (IR) in all cases (if you followed the rule from §1.2) will be a value combined from php arrays and scalars. That is you always can encode IR in JSON format. You also can revive instance from corresponding IR.



#### 4.1 Method `unwrap`

You can use this method for retrieving internal representation.

In case of `Type` IR will be just value: for scalars of types `bool`, `int`, `float` and `string` it will be a scalar, for complex types it will be an array. For instances of classes it will be result of method `unwrap` calling on the instance. That is, if you are about to use your own custom class as a typed value you need to implement that method.

In case of `Enum` it will be just an integer representing index of variant.

In case of `Variants` it will be an array of two elements, where

0. Is index of variant
1. Array of IRs of values in payload



#### 4.2 Method `wrap`

Revives instance from IR. This method is static, so you should call it on class.



#### 4.3 Method `encode`

Returns IR in JSON encoding.



#### 4.4 Method `decode`

Revives instance from JSON.