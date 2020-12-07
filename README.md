# Strong types in PHP

Since PHP 7.0.0 there is possibility to turn on type checking with `declare(strict_types=1);` statement. So lets use it on 110%.

### Motivation

We used to think of PHP variables as boxes where you can put anything. Does it mean that there is NO types in PHP at all? Of course, not. Types are imminent part of any programming language.

There always was kinda magic we calling type casting. But is this magic black or white? From my experience I can say that about 80% of bugs I ever fixed in PHP code was related to types.

Fortunately, we have type checking in PHP and it prevents us from making stupid type errors. If you still don't understand why types are important, just imageine that you have a program encountered a type error. Who will report you about this error? Angry customer? In case of type checking that kind of code will never leak to production - you even couldn't launch it (you'll get a TypeError).

Functional languages have bunch of good types like Maybe, Either and so on. Inspired by Haskell and Elm this package provides a platform for creating strong typed and immutable data structures

PHP offers set of pre-defined types such as `int`, `bool` or `string`. With type-hinting you can also use class names as a type. Phpt library provides set of abstract classes, which can be used as a basis for your own types.

### Installation

Installation with composer: `composer require artem-vorobev/phpt`

You need to require autoload file. Then all Phpt classes are ready!

```php
require('vendor/autoload.php');
```

 





## Basic usage



### 1. First example. Lists.

List is a typed array which consists of *N* elements of the same type.

Class `Phpt\Types\ListOf` can be used for creating lists. Let's create list of integers:

```php
use Phpt\Types\ListOf;
class ListOfInt extends ListOf
{
  static $type = ['int'];
}

$list = new ListOfInt([1, 2, 3, 4, 5]);
```

Class `ListOf` implements built-in PHP interfaces such as `ArrayAccess`, `Countable` and `Iterator`, so you can treat the list object as normal array. Example below shows definition of the function which calculates average value of list of integers:

```php
function average(ListOfInt $list): int
{
  $sum = 0;
  foreach ($list as $int) $sum += $int; // You can use list in foreach loops
  return $sum/count($list); // count is also supported
}
```

You can also use `[$i]` notation to access elements of list:

```php
$list = new ListOfInt([1, 2, 3, 4, 5]);

$list[0] == 1 // true
$list[4] == 5 // true
$list[100500] // Exception! This key is not defined!
```

#### Immutability and mutations

Comparing to PHP arrays lists are immutable. So, you can't modify elements of the list. But it is not a problem. Let's see how you can add elements to list:

```php
$list = new ListOfInt([]); // empty list
$list = $list->push(1);  // [1]
$list = $list->push(2, 3, 4, 5); // [1,2,3,4,5] 
```

So, instead of mutation, every time new list will be created. If you need to have old copy, save result into new variable. If not - just overwrite the old one!

There is a bunch of function you can use with lists:

- `push(...$elements)` Returns new list with _n_ values pushed to the end
- `pop(int $n=1)` Returns new list with _n_ values dropped from the end
- `shift(int $n=1)` Returns new list with _n_ values pushed to the start
- `unshift(...$elements)` Returns new list with _n_ values dropped from the start
- `splic(int $offset, int $length, array $replacement=[])` Returns new list "spliced" by function `array_splice`

Method `with` allows you to re-assign values according their keys:

```php
$list1 = new ListOfInt([1, 2, 3, 4, 5]);
$list2 = $list1->with([0 => 5, 1 => 4]);

// List 1 is still [1,2,3,4,5]
// List 2 is [5,4,3,4,5]
```

If you need some specific function like `array_unique` which is not implemented, you can use method `unwrap` which is opposite to constructor: it returns real PHP array with the same values:

```php
$list = new ListOfInt([3,5,1,2,4]);
$php_array = $list->unwrap();				// $php_array is [3,5,1,2,4]
asort($php_array);									// $php_array is [2 => 1, 3 => 2, 0 => 3, 4 => 4, 1 => 5,]
```

But there is one serious caveat: you can't now put sorted list into ListOfInt's constructor. Reason is simple: array is not _regular_. It means that key of element does not show anymore real position in array. As you can see, number 1 is first, but it has key 2. What we need to do here is to get rid of these keys using PHP function `array_values` :

```php
$sortedList = new ListOfInt(array_values($php_array));
```



### 2. Other typed values

#### Tuples

If you know Python you already know what tuples is. Tuple is set of elements with fixed length and types of elements. For example, we can represent coordinate in 3D space with tuple:

```php
use Phpt\Types\Tuple;
class Point3D extends Tuple
{
  static $type = ['float', 'float', 'float'];
}

$origin = new Point3D([0.0, 0.0, 0.0]);
```

`Tuple` also implements interfaces `ArrayAccess`, `Countable` and `Iterator`, but ther is no methods like `push` and `splice` which could change the length of the tuple. But you still can use `count`,  `foreach` and access element using `[$i]` syntax.

You can also replace elements with method `with`.



#### Records

Records are just associative arrays. Like tuples they have fixed length. Let's create simple record representing user of the system:

```php
use Phpt\Types\Record;
class User extends Record
{
  static $type = [
    'id' => 'int',
    'name' => 'string',
    'rating' => 'float'
  ];
}

$user = new User([
  'id' => 1234,
  'name' => 'John',
  'rating' => 4.9
]);
```

We can use `count` , array access and `foreach` with records. Method `with` also works. But you can access elements also in OOP style:

```php
echo $user->name; // will print "John"
```



#### Enum

Enums are coming from C language. If you have some variants (more than two, so you can't use boolean) you can create an enum. For example, we can create model for traffic light:

```php
use Phpt\Types\Enum;
class TrafficLight extends Enum
{
  static $type = [':Red', ':Yellow', ':Green'];
}

$t = new TrafficLight('Red');
```

Names of variants should start with semicolon. You can match value using special `is...` methods:

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



#### Variants

Variants are mix of Enum with typed values. While enum can have only state selected from possible variants, Variants can have for each variant one typed value. For example, if some value can be either integer or string, we can represent it's type as described below:

```php
use Phpt\Types\Variants;
class IntOrString extends Variants
{
  static $type = [
    ':Int' => 'int',
    ':Str' => 'string'
  ];
}

$x = new IntOrString('Int', 42);
```

You can match value using `is...` methods:

```php
if ($x->isInt()) {
  // Do computation...
}
if ($t->isStr()) {
  // Do smth else
}
```

You can access inner value using OOP style (name of property can be in lower case for readability):

```php
$y = new IntOrString('Str', 'foo');

if ($y->isStr()) {
  echo $y->str; // will print "foo";
}
```



#### Maybe

Maybe is a particular case of variants, defined in Phpt library as follows:

```php
abstract class Maybe extends Variants
{
  static $variants = [
    ':Just' => 'a',			// Type variable. Any type can be here depends on child class.
    ':Nothing' => null  // No type here. That means that this variant does not expect any value.
  ];
}
```

If you know Haskell or any other functional language, you may know about "Maybe". "Maybe" is special type, which represents value in context of possible failure. For example, functions like `strpos` returns result only if result is found. In PHP world in case of failure `strpos` returns false which may cause sometimes type errors. In function languages `strpos` would return `MaybeInt` which can be defined as follows:

```php
class MaybeInt extends Maybe
{
  static $a = 'int'; // here we define what 'a' means.
}
```

As normal instance of `Variants` instance of `MaybeInt` also provides `is...` methods and named properties:

```php
$result = maybe_strpos($needle, $haystack); // imagine that this PHP function exists and returns MaybeInt
if ($result->isNothing()) {
  // Not found
}
if ($result->isJust()) {
  $pos = $result->just;
  // Do something with this number
}
```

Of course, in case  `Nothing` there is no available property to read.



#### Tree

Tree is example of power of Variants. You can define variants recursively! In our example Tree is binary and can be: 1) empty 2) contains some integer data and two inner branches:

```php
class Tree extends Variants
{
  static $type = [
    ':Empty' => null, // Tree can be empty
    ':Nodes' => [     // Or it contains data and left and right subtrees
      'data' => 'int', 
      'left' => Tree::class,
      'right' => Tree::class
    ]
  ];
}
```



### 3. Type checking 

Main feature of this library is type checking. You can always be sure that you will deal with desired type of data. Library is very strict and if there will be type mismatch, you'll get an exception. Types can be very complex, but they have simple notation.

When we defined `ListOfInt` class we already used type notation:

```php
class ListOfInt extends ListOf
{
  static $type = ['int']; // This construction means "List of integers".
}
```

Variable $type is only one required value to be defined. It can be:

- Scalar value
  - Integer, defined by string `'int'`
  - Float, defined by string `'float'`
  - String, defined by string `'string'`
  - Boolean, defined by string `'bool'`
  - Instance of class, defined by it's class name. Class should implement `Php\Abstract\TypedValueInterface`.
  
- Complex value
  - List, defined by array with one element `[*]`, where * is either scalar of complex type. All elements in list must have same type.
  - Tuple, defined by array with _n_ elements `[*, ...]`, where _n_ > 1 and * means the same as for lists. Elements of tuple may have different types.
  - Record, defined by associative array `['key1' => *, 'key2' => *, ...]`, Record should have at least one string key. Elements of record may have different types.
  - Enum, defined by array `[':Variant1', ':Variant2', ...]`
  - Variants, defined by `[':Variant1' => *, ':Variant2' => *, ...]`
  
- Type variable. Letters from `'a'` to `'h'` are resefved for type variables. So, you can create abstract type with type variables instead of final types and in child classes define those types.

  

### 4. Nested structures

With nested structure you can define very complex values. And library provides very straightforward interface for data manipulation. For example, lets  create model for contacts as list of records:

```php
class ListOfRecords extends ListOf
{
  static $type = [[
    'name' => 'string',
    'email' => 'string'
  ]]
}


$myContacts = getContacts(); // assume that this function exists and return ListOfRecords

// lets print out our contacts
foreach ($myContacts as $contact) {
  echo $contact->name.': '.$contact->email."\n";
}

// add new contact
$myContacts = $myContacts->push(['name' => 'Amy', 'email' => 'amy.adams@gmail.com']);

// edit one of contacts
$myContacts = $myContacts[$n]->with(['email' => 'new.email@mail.com']);
```



### 5. Other features

#### Checking equality

There is method `equal($value)` which takes value you wan compare with. Two typed values are equal if:

1. They both are instances of the same class, i.e both have the same type
2. They have equal content

#### JSON interoperability

Methods `decode` and `encode` are available on each typed values.



### 6. Error codes

| Code | String representation | Defined in |
| ---- | --------------------- | ------- |
| 100 | Int value expected. | src/Types/Enum.php:14 |
| 101 | Unexpected value $ir. | src/Types/Enum.php:16 |
| 300 | Element [$offset] is not defined. | src/Abstractions/ArrayTrait.php:18 |
| 301 | Object is immutable. | src/Abstractions/ArrayTrait.php:42, 54 |
| 400 | Wrong type signature given. | src/Abstractions/Enum.php:9 |
| 401 | $typeError | src/Abstractions/Enum.php:10 |
| 402 | Unknown variant "$variant" used by function "$name". | src/Abstractions/Enum.php:36 |
| 403 | Unknown method "$name". | src/Abstractions/Enum.php:40 |
| 500 | Wrong type signature given. | src/Abstractions/TypedTuple.php:11 |
| 501 | $typeError | src/Abstractions/TypedTuple.php:12 |
| 502 | Key $i is not defined. | src/Abstractions/TypedTuple.php:43 |
| 600 | JSON given is malformed. | src/Abstractions/TypedValue.php:189 |
| 700 | Class "$signature" should implement TypedValueInteface. | src/Abstractions/TypeSignature.php:84 |
| 701 | Unknown scalar type "$signature". | src/Abstractions/TypeSignature.php:88 |
| 702 | Complex type signature is empty. | src/Abstractions/TypeSignature.php:93 |
| 703 | Incorrect type signature. | src/Abstractions/TypeSignature.php:126 |
| 704 | Unknown method "$name". | src/Abstractions/TypeSignature.php:142 |
| 705 | Unknown property "$name". | src/Abstractions/TypeSignature.php:167 |
| 800 | Wrong type signature given. | src/Abstractions/TypeVariants.php:9 |
| 801 | $typeError | src/Abstractions/TypeVariants.php:10 |
| 802 | Unknown constructor "$constructor" used by function "$name". | src/Abstractions/TypeVariants.php:51 |
| 803 | Unknown method "$name". | src/Abstractions/TypeVariants.php:55 |
| 804 | Unknown property "$name". | src/Abstractions/TypeVariants.php:72 |
| 900 | Wrong type signature given. | src/Abstractions/TypedRecord.php:11 |
| 901 | $typeError | src/Abstractions/TypedRecord.php:12 |
| 902 | Key $key is not defined. | src/Abstractions/TypedRecord.php:43 |
| 903 | Property $name is not defined. | src/Abstractions/TypedRecord.php:72 |
| 1000 | Wrong type signature given. | src/Abstractions/TypedList.php:11 |
| 1001 | $typeError | src/Abstractions/TypedList.php:12, 88, 116 |
| 1002 | Key $key is not defined. | src/Abstractions/TypedList.php:127 |