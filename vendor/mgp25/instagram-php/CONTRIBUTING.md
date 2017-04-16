# Contributing to Instagram API

:+1::tada: First off, thanks for taking the time to contribute! :tada::+1:

The following is a set of guidelines for contributing to Instagram API, which are hosted in [Instagram API repository](https://github.com/mgp25/Instagram-API) on GitHub.
These are just guidelines, not rules. Use your best judgment, and feel free to propose changes to this document in a pull request.

- [What should I know before I get started?](#what-should-i-know-before-i-get-started)
  * [Code of Conduct](#code-of-conduct)

- [Basic rules](#basic-rules)
  * [Commits](#commits)
  * [Modifying anything in the existing code](#modifying-anything-in-the-existing-code)

- [Styleguides](#styleguide)
  * [Namespaces](#namespaces)
  * [Functions and Variables](#functions-and-variables)
  * [Function Documentation](#function-documentation)
  * [Exceptions](#exceptions)

- [Contributing-new-endpoints](#contributing-new-endpoints)


## What should I know before I get started?

### Code of Conduct

This project adheres to the Contributor Covenant [code of conduct](CODE_OF_CONDUCT.md).
By participating, you are expected to uphold this code.
Please report any unacceptable behavior.

## Basic rules

Important! Your contributions must follow all of these rules for a consistent and bug-free project.

This is a document which, among other things, describes PSR-4 class autoloading, clean commits, how to handle/document exceptions, how to structure function arguments, naming clear variables, always adding/updating PHPdoc blocks in all affected functions, and how to verify that your changes don't _break everything_ when performing big changes to existing functions or adding brand new functions and classes.


### Commits

- You **MUST** try to **separate** work into smaller commits. So if you `"changed Utils.php to fix getSeconds and changed Request.php to fix Request()"`, then make **TWO SEPARATE COMMITS**, so that we can easily find your changes in the project history, and can easily revert any individual broken changes without losing tons of other code too! So remember: 1 commit per task. :smile:

- Use the detailed description field of your commit, to document why you did certain changes if it's not an obvious change (such as a typo fix). Your description gets added to the history of the project so that we can know what you were thinking, if we need to check the change again later.

- **Name** your single-file commits `"AffectedClassName: Change description"` and keep the total length of the summary line at 50 characters or less in total (the Git/GitHub standard).

- If your change NEEDS to affect multiple files, then it is SOMETIMES okay to leave out the classname part from the summary, but in that case you _must_ write a very clear and descriptive summary to explain it. _But_ usually you _should_ still include the classname even for multi-file edits. For example, renaming a function in Utils and having to change the name of it in other files is still a `"Utils: Renamed bleh() to bloop()"` commit.

Examples of BAD commit summaries:

```
Edit something
Fix this
Utils+request changes
```

Examples of GOOD commit summaries:

```
Utils: Fix formatting of getSeconds
Request: Send cropping information
Changed all number_format() to round() everywhere
Response: Parse timestamps as floats
```


### Modifying anything in the existing code

- If you want to change a public API function's parameters (particularly in `src/Instagram.php`), think EXTREMELY hard about NOT doing it. And if you absolutely MUST do it (such as adding an important new parameter), then you MUST add it in a BACKWARDS-COMPATIBLE WAY, by adding it as the LAST parameter of the argument list AND providing a sensible DEFAULT VALUE for it so that people's CURRENT projects based on this library continue working and DON'T BREAK due to your function definition changes!

- Do NOT look at your changes in isolation. Look at the BIG PICTURE of what your change now does to the REST of the codebase.

- For example, if you change the returned variable type from a function (such as from an `"int"` to a `"string"`), then you MUST update the function's PHPdoc block to document the new return value (with `@return string`), and you MUST also slowly and carefully check ALL OTHER CODE that relied on the OLD behavior of that function. There's a command-line tool called "grep" (or an even better one especially made for programmers, called ["ag" aka "the silver searcher"](https://github.com/ggreer/the_silver_searcher)) to search for text in files. USE IT to check ALL other code locations that called your modified function, and make sure that you didn't break ANY of them AT ALL!

- In fact, ANY TIME that you change a function's ARGUMENTS, RETURN VALUE or THROWN EXCEPTIONS (new/deleted ones), then you MUST update the function's PHPdoc block to match the new truth. And you MUST check ALL other functions that CALL your function, and update those too. For example let's say they DON'T handle a new exception you're now throwing, in which case you MUST now update THEIR `@throws` documentation to document the fact that THEY now let yet another exception bubble up. And then you MUST search for the functions that called THOSE updated functions and update THEIR documentation TOO, all the way until you've reached the top and have FULLY documented what exceptions are being thrown in the whole chain of function calls.

- You MUST ALWAYS update the PHPdoc blocks EVERYWHERE that's affected by your changes (whenever you've changed a functions ARGUMENTS or RETURN type or what it THROWS (any new/deleted exceptions)), because imagine if we NEVER update the blocks EVERYWHERE that's affected by your change. We would then have something like `/** @param $a, $b, $c */ function foo ($x)` and sudden, critical exceptions that aren't getting handled and thus KILL the program.

Here's a checklist for what you MUST do to ENSURE totally correct documentation EVERY TIME that you make a CHANGE to a function's ARGUMENTS or RETURN TYPE or EXCEPTIONS of an existing function, OR when you introduce a NEW function whose use is being added to any existing functions.

1. You MUST ALWAYS use grep/ag. Find EVERY other code location that uses your new/modified function.

2. Check: Did your changes just BREAK EVERYTHING somewhere else, which now has to be updated to match? Almost GUARANTEED the answer is YES, and you MUST update the other locations that expected the old function behavior/return type/parameters/exceptions.

3. Check: Do you need to also UPDATE the PHPdoc for those OTHER functions too? OFTEN the answer is YES. For example, if you've changed the exceptions that a deep, internal function throws, then you MUST either catch it in all higher functions and do something with it, OR let it pass through them upwards. And if you do let it pass through and bubble up then you MUST ALSO update the PHPdoc for THAT higher function to say `"@throws TheNewException"` (or delete something in case you changed a subfunction to no longer throw).

4. If your updates to the affected higher-level functions in steps 2/3 means that THOSE other functions now ALSO behave differently (meaning THEY have new return values/exceptions/parameters), then you MUST also do ANOTHER grep/ag to check for anything that uses THOSE functions, and update all of those code locations TOO. And continue that way up the entire chain of functions that call each other, until you reach the top of the project, so that the entire chain of function calls is caught/documented properly. Otherwise, those unexpected (undocumented) exceptions will terminate PHP, so this is very important!



## Styleguides

### Namespaces

- Organize all classes into logical namespaces.

- We follow the PSR-4 Autoloading standard, which means that you MUST only have ONE class per source code `.php` file. And the namespace AND classname MUST match BOTH its disk path AND its `.php` filename. In our project, the `src/` folder is the `InstagramAPI` namespace, and everything under that is for its subnamespaces (folders) and our classes (PHP files).

Example of a proper class in our top-level namespace:

```php
src/Something.php:
<?php

namespace InstagramAPI;

class Something
{
    ....
}
```

Example of a proper class in a sub-namespace:

```php
src/Wonderful/Things/Something.php:
<?php

namespace InstagramAPI\Wonderful\Things;

class Something
{
    ....
}
```

### Functions and Variables

- You MUST split all function declaration/definition arguments onto separate lines, but only IF the function takes arguments. This is done for clarity, and to avoid unintentional bugs by contributors. It ensures that any changes to function arguments will be diffed as a single-line change. It's more readable. And it's the style _we_ use.

Example of a function that takes no arguments:

```php
public function doSomething()
{
    ...
}
```

Example of a function that takes one argument:

```php
public function doSomething(
    $foo = null)
{
    ...
}
```

Example of a function that takes multiple arguments:

```php
public function doSomething(
    $foo,
    $bar = '',
    $baz = null)
{
    ...
}
```

- All properties and functions MUST be named using `camelCase`, NOT `snake_case`. (The _only_ exception to this rule is  Instagram's server response property objects, meaning everything in the `src/Response/` folder, since _their_ server replies use underscores.)
- Private and protected properties/functions MUST be _prefixed_ with an underscore, to clearly show that they are internal inside the class and _cannot_ be used from the outside. But the PUBLIC properties/functions MUST _NEVER_ be prefixed with an underscore (_except_ the _obvious_, specially named, built-in PHP double-underscore ones such as `public function __construct()`). And also note that function _arguments_ (such as in setters) MUST NEVER be prefixed by underscores.

Example of a class with proper function and property underscore prefixes for private/protected members, and with the proper function argument style:

```php
class Something
{
    public $publicProperty;
    protected $_protectedProperty;
    private $_privateProperty;

    public function getProtectedProperty()
    {
        return $this->_protectedProperty;
    }

    public function setProtectedProperty(
        $protectedProperty)
    {
        $this->_protectedProperty = $protectedProperty;
    }

    public function getPublicProperty()
    {
        return $this->_publicProperty;
    }

    public function setPublicProperty(
        $publicProperty)
    {
        $this->publicProperty = $publicProperty;
    }

    protected function _somethingInternal()
    {
        ...
    }

    ...
}
```

- All functions and variables MUST have descriptive names that document their purpose automatically. Use names like `$videoFilename` and `$deviceInfo` and so on, so that the code documents itself instead of needing tons of comments to explain what each step is doing.

Examples of BAD variable names:

```php
$x = $po + $py;
$w = floor($h * $ar);
```

Examples of GOOD variable names:

```php
$endpoint = $this->url.'?'.http_build_query($this->params);
$this->_aspectRatio = $this->_width / $this->_height;
$width = floor($this->_height * $this->_maxAspectRatio);
```

- All functions MUST have occasional comments that explain what they're doing in their various substeps. Look at our codebase and follow our commenting style.

- Our comments start with a capital letter and end in punctuation, and describe the purpose in as few words as possible, such as: `// Default request options (immutable after client creation).`

- All functions MUST do as little work as possible, so that they are easy to maintain and bugfix.

Example of a GOOD function layout:

```php
function requestVideoURL(...);

function uploadVideoChunks(...);

function configureVideo(...);

function uploadVideo(...)
{
    $url = $this->requestVideoURL();
    if (...handle any errors from the previous function)

    $uploadResult = $this->uploadVideoChunks($url, ...);

    $this->configureVideo($uploadResult, ...);
}
```

Example of a BAD function layout:

```php
function uploadVideo(...)
{
    // Request upload URL.
    // Upload video data to URL.
    // Configure its location property.
    // Post it to a timeline.
    // Call your grandmother.
    // Make some tea.
    // ...and 500 other lines of code.
}
```

- All function parameter lists MUST be well-thought out so that they list the most important arguments FIRST and so that they are as SIMPLE as possible to EXTEND in the FUTURE, since Instagram's API changes occasionally.

Avoid this kind of function template:

```php
function uploadVideo($videoFilename, $filter, $url, $caption, $userTags, $hashTags);
```

Make such multi-argument functions take future-extensible option-arrays instead, especially if you expect that more properties may be added in the future.

Furthermore, its `uploadVideo` name is too generic. What if we _later_ need to be able to upload Story videos, when Instagram added its Story feature? And Album videos? Suddenly, the existing `uploadVideo` function name would be a huge problem for us.

So the above would instead be PROPERLY designed as follows:

```php
function uploadTimelineVideo($videoFilename, array $metadata);
```

Now users can just say `uploadTimelineVideo($videoFilename, ['hashtags'=>$hashTags]);`, and we can easily add more metadata fields in the future without ever breaking backwards-compatibility with projects that are using our function! And since the function name is good and _specific_, it also means that we can easily add _other_ kinds of "video" upload functions for any _future features_ Instagram introduces, simply by creating new functions such as `uploadStoryVideo`, which gives us total freedom to implement Instagram's new features without breaking backwards-compatibility with anyone using the _other_ functions.

### Function Documentation

- All functions MUST have _COMPLETE_ PHPdoc doc-blocks. The critically important information is the single-sentence `summary-line` (ALWAYS), then the `detailed description` (if necessary), then the `@param` descriptions (if any), then the `@throws` (one for EVERY type of exception that it throws, even uncaught ones thrown from DEEPER functions called within this function), then the `@return` (if the function returns something), and lastly one or more `@see` if there's any need for a documentation reference to a URL or another function or class.

Example of a properly documented function:

```php
    /**
     * Generates a User Agent string from a Device (<< that is the REQUIRED ONE-SENTENCE summary-line).
     *
     * [All lines after that are the optional description. This function didn't need any,
     *  but you CAN use this area to provide extra information describing things worth knowing.]
     *
     * @param \InstagramAPI\Devices\Device $device The Android device.
     * @param string[]|null                $names (optional) Array of name-strings.
     *
     * @throws \InvalidArgumentException                  If the device parameter is invalid.
     * @throws \InstagramAPI\Exception\InstagramException In case of invalid or failed API response.
     *
     * @return string
     *
     * @see otherFunction()
     * @see http://some-url...
     */
    public static function buildUserAgent(
        Device $device,
        $names = null)
    {
        ...
    }
```

- You MUST take EXTREMELY GOOD CARE to ALWAYS _perfectly_ document ALL parameters, the EXACT return-type, and ALL thrown exceptions. All other project developers RELY on the function-documentation ALWAYS being CORRECT! With incorrect documentation, other developers would make incorrect assumptions and _severe_ bugs would be introduced!

### Exceptions

- ALL thrown exceptions that can happen inside a function or in ANY of its SUB-FUNCTION calls MUST be documented as `@throws`, so that we get a COMPLETE OVERVIEW of ALL exceptions that may be thrown when we call the function. YES, that EVEN means exceptions that come from deeper function calls, whose exceptions are NOT being caught by your function and which will therefore bubble up if they're thrown by those deeper sub-functions!
- Always remember that Exceptions WILL CRITICALLY BREAK ALL OTHER CODE AND STOP PHP'S EXECUTION if not handled or documented properly! They are a LOT of responsibility! So you MUST put a LOT OF TIME AND EFFORT into PROPERLY handling (_catching and doing something_) for ALL exceptions that your function should handle, AND adding PHPdoc _documentation_ about the ones that your function DOESN'T catch/handle internally and which WILL therefore bubble upwards and would possibly BREAK other code (which is EXACTLY what would happen if an exception ISN'T documented by you and someone then uses your bad function and doesn't "catch" your exception since YOU didn't tell them that it can be thrown)!
- All of our internal exceptions derive from `\InstagramAPI\Exception\InstagramException`, so it's always safe to declare that one as a `@throws \InstagramAPI\Exception\InstagramException` if you're calling anything that throws exceptions based on our internal `src/Exception/*.php` system. But it's even better if you can pinpoint which exact exceptions are thrown, by looking at the functions you're calling and seeing their `@throws` documentation, WHICH OF COURSE DEPENDS ON PEOPLE HAVING WRITTEN PROPER `@throws` FOR THOSE OTHER FUNCTIONS SO THAT _YOU_ KNOW WHAT THE FUNCTIONS YOU'RE CALLING WILL THROW. DO YOU SEE _NOW_ HOW IMPORTANT IT IS TO DECLARE EXCEPTIONS PROPERLY AND TO _ALWAYS_ KEEP THAT LIST UP TO DATE
- Whenever you are using an EXTERNAL LIBRARY that throws its own custom exceptions (meaning NOT one of the standard PHP ones such as `\Exception` or `\InvalidArgumentException`, etc), then you MUST ALWAYS re-wrap the exception into some appropriate exception from our own library instead, otherwise users will not be able to say `catch (\InstagramAPI\Exception\InstagramException $e)`, since the 3rd party exceptions wouldn't be derived from our base exception and wouldn't be caught, thus breaking the user's program. To solve that, look at the design of our `src/Exception/NetworkException.php`, which we use in `src/Client.php` to re-wrap all Guzzle exceptions into our own exception type instead. Read the source-code of our NetworkException and it will explain how to properly re-wrap 3rd party exceptions and how to ensure that your re-wrapped exception will give users helpful messages and helpful stack traces.


# Contributing new endpoints

In order to add endpoints to the API you will need to capture the requests first. For that, you can use any HTTPS proxy you want. You can find a lot of information about this on the internet. Remember that you need to install a root CA (Certificate Authority) in your device so that the proxy can decrypt the requests and show them to you.


Once you have the endpoint and necessary parameters, how do you add them to this library? Easy, you can follow this example:

```php
    public function getAwesome()
    {
        return $this->request('awesome/endpoint/')
        ->setSignedPost(false)
        ->addPost('_uuid', $this->uuid)
        ->addPost('user_ids', implode(',', $userList))
        ->addPost('_csrftoken', $this->token)
        ->getResponse(new Response\AwesomeResponse());
    }
```

In the example above you can see `('awesome/endpoint/')` which is the endpoint you captured. We are simulating a POST request, so you can add POST parameters easily by doing `->addPost('_uuid', $this->uuid)`.

Which is basically:

```php
->addPost(key, value)
```

Where key is the name of the POST param, and value is whatever value the server requires for that parameter.

Some of the requests are signed. This means there is a hash concatenated to the JSON. In order to make a signed request, we can enable or disable signing with the following line:

```php
->setSignedPost($isSigned)
```

`$isSigned` is boolean, if you want a signed request, you simply set it to `true`.

If the request is a GET request, you can add the GET query parameters like this (instead of using `addPost`):

```php
->addParams(key, value)
```

And finally, we always end with the `getResponse` function call, which will read the response and return an object with all of the server response values:

```php
->getResponse(new Response\AwesomeResponse());
```

Now you might be wondering how to create that response class? But there is nothing to worry about, it's very simple.

Imagine that you have the following response:

```json
{"items": [{"user": {"is_verified": false, "has_anonymous_profile_picture": false, "is_private": false, "full_name": "awesome", "username": "awesome", "pk": "uid", "profile_pic_url": "profilepic"}, "large_urls": [], "caption": "", "thumbnail_urls": ["thumb1", "thumb2", "thumb3", "thumb4"]}], "status": "ok"}
```

You can use [http://jsoneditoronline.org](http://jsoneditoronline.org/) for a better visualization:

<img src="https://s29.postimg.org/3xyopcbg7/insta_help.jpg" width="300">

So your new `src/Response/AwesomeResponse.php` class should contain one public var named `items`. Our magical JSONMapper object mapping system also needs a PHPdoc comment to tell us if the property is another class, an array, a string, a string array, etc. By default, if you don't specify any comment, it will read the JSON value as whatever type PHP detected it as internally (such as a string, int, float, bool, etc).

In this scenario:

```php
    /**
     * @var Model\Suggestion[]
     */
    public $items;
 ```

The `$items` property will contain an array of Suggestion model objects. And `src/Response/Model/Suggestion.php` will look like this:

```php
<?php

namespace InstagramAPI\Response\Model;

class Suggestion extends \InstagramAPI\Response
{
    public $media_infos;
    public $social_context;
    public $algorithm;
    /**
     * @var string[]
     */
    public $thumbnail_urls;
    public $value;
    public $caption;
    /**
     * @var User
     */
    public $user;
    /**
     * @var string[]
     */
    public $large_urls;
    public $media_ids;
    public $icon;
}
```

Here in this `Suggestion` class you can see many variables that didn't appear in our example endpoint's response, but that's because many other requests _re-use_ the same object, and depending the request, the response variables may differ. Also note that unlike the AwesomeResponse class, the actual Model objects (the files in in `src/Response/Model/`) _don't_ have to use the "Model\" prefix when referring to other model objects, since they are in the same namespace already.

Note that any Model objects relating to Media IDs, PKs, User PKs, etc, _must_ be declared as a `/** @var string */`, otherwise they may be handled as a float/int which won't fit on 32-bit CPUs and will truncate the number, leading to the wrong data. Just look at all other Model objects that are already in this project, and be sure that any ID/PK fields in your new Model object are properly tagged as `string` type!

Lastly, our `src/Response/AwesomeResponse.php` should look as follows:

```php
<?php

namespace InstagramAPI\Response;

class AwesomeResponse extends \InstagramAPI\Response
{
    /**
     * @var Model\Suggestion[]
     */
    public $items;
}
```

Now you can test your new endpoint, in order to see the response object:

```
$a = $i->getAwesome();
var_dump($a); // this will print the response object
```

And finally, how do you access the object's data? Via the magical `AutoPropertyHandler` which you inherited from thanks to always extending from the `\InstagramAPI\Response` object. It automatically creates getters and setters for all properties.

```php
$items = $a->getItems();
$user = $items[0]->getUser();
```

Hope you find this useful. :smile:
