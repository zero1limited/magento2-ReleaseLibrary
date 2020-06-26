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

## Move a category
```php
$this->setupUtility->moveCategory(
    CATEGORY_ID_TO_MOVE
    MOVE_TO_THIS_CATEGORY_ID,
);
```
You can also supply a 3rd argument "after", if you would like the moved category to be placed after a specific category.

## Create CMS Blocks
This allows you to create a number of CMS Blocks without needing to add the html into your setup script.

### Configuration
Before being able to do this you must create another module or use your current release module.
This module must contain the directory `block_source`

### Example
In this example I have a module called "My_ReleaseModule" which contains:
```
My_ReleaseModule/
    block_source/
        1.0.0/
            a-new-custom-block.html
        1.0.1/
            custom-block.html
            promo-block.html
```

```php
// configure setup utility to use my module
$this->setupUtility->setSourceModule('My_ReleaseModule);

// create all the blocks in `1.0.1`
$this->setupUtility->createBlocksFromDir(
    $this->setupUtility->getBlockSourceDirectory().'/1.0.1/'
);
```
This will result in two cms blocks being created
1.
  - name: custom block
  - id: custom-block
2. 
  - name: promo block
  - id: promo-block
  
## Create CMS Pages
This allows you to create a number of CMS Pages without needing to add the html into your setup script.

### Configuration
Before being able to do this you must create another module or use your current release module.
This module must contain the directory `page_source`

### Example
In this example I have a module called "My_ReleaseModule" which contains:
```
My_ReleaseModule/
    page_source/
        1.0.0/
            a-new-cms-page.html
        1.0.1/
            custom-page.html
            contact-us.html
```

```php
// configure setup utility to use my module
$this->setupUtility->setSourceModule('My_ReleaseModule);

// create all the pages in `1.0.1`
$this->setupUtility->createPagesFromDir(
    $this->setupUtility->getPageSourceDirectory().'/1.0.1/'
);
```
This will result in two cms pages being created
1.
  - name: custom page
  - id: custom-page
2. 
  - name: contact us
  - id: contact-us
  
## Set Config
Update / set config values 

### Example 1 - set single value
```php
$this->setupUtility->setConfig(
    [['design/head/demonotice', 1]],
    'default',
    0
);
```
### Example 2 - set multiple values for default scope
```php
$this->setupUtility->setConfig([
    ['design/head/demonotice', 1],
    ['web/cookie/cookie_httponly', 1]
]);
```

### Example 3 - set multiple values for multiple scopes
```php
$this->setupUtility->setConfig([
    ['design/head/demonotice', 1], // will be set at default
    ['web/cookie/cookie_httponly', 1] // will be set at default
    ['web/cookie/cookie_path', '/', 'stores', 1], // will be set for store 1
    ['web/cookie/cookie_path', '/', 'stores', 2], // will be set for store 2
]);
```