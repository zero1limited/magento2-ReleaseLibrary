# magento2-ReleaseLibrary
A module to provided easy to use functions in updgrade/install scripts


# Usage
To use the functions in your setup or install script add the util class to your objects constructor
```php
/** @var \Zero1\ReleaseLibrary\Utility **/
protected $setupUtility;

public function __construct(
    \Zero1\ReleaseLibrary\Utility $setupUtility
){
    $this->setupUtility = $setupUtility;
}
```

You will then be able to call `$this->setupUtility`.

# Functions

## Create / Update a custom variable

```php
$this->setupUtility->createCustomVariable(
    'Variable Code,
    'Variable Name',
    'HTML Value',
    'Plain Value'
);
```
You can also supply a 5th argument:
- `true` - update if variable if one with the same code already exists
- `false` - throw a EntityAlreadyExistsException if a variable with the same code exists