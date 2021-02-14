# Composer Suites

[![CircleCI](https://circleci.com/gh/Sweetchuck/composer-suite.svg?style=svg)](https://circleci.com/gh/Sweetchuck/composer-suite)
[![codecov](https://codecov.io/gh/Sweetchuck/composer-suite/branch/1.x/graph/badge.svg)](https://codecov.io/gh/Sweetchuck/composer-suite)

Generates multiple variations of the original `composer.json`

1. You have to define the differences in the
   composer.json#/extra/composer-suite, see the examples below. 
2. Next step is to generate the alternative composer.json files by running the
   following command:  
   `composer -vv suite:generate`
3. Activate one of the alternative composer.json file by setting the
   [COMPOSER](https://getcomposer.org/doc/03-cli.md#composer) environment
   variable.
   1. `export COMPOSER='composer.my-suite-01.json'`
   2. `composer update --lock`


## Example composer.json
```json
{
    "require": {
        "symfony/console": "^4.0",
        "symfony/process": "^4.0",
        "a/b": "^1.0",
        "c/d": "^1.0",
        "e/f": "^1.0"
    },
    "extra": {
        "composer-suite": {
            "my-suite-01": [
                {
                    "type": "replaceRecursive",
                    "config": {
                        "items": {
                            "require": {
                                "symfony/console": "^5.0",
                                "symfony/process": "^5.0"
                            }
                        }
                    }
                },
                {
                    "type": "unset",
                    "config": {
                        "parents": [
                            "require",
                            [
                                "a/b",
                                "e/f"
                            ]
                        ]
                    }
                }
            ]
        }
    }
}
```

Run: `composer suite:generate`  
Result is a "composer.my-suite-01.json" file with the following content:
```json
{
    "require": {
        "symfony/console": "^5.0",
        "symfony/process": "^5.0",
        "c/d": "^1.0"
    },
    "extra": {}
}
```

Run: `COMPOSER='composer.my-suite-01.json' composer install`


## Suites

You can have as many suites as many you want.  
In the example above there is only one (my-suite-01), but there can be more than
one.


## Actions

You can define different array manipulation actions under the
`extra/composer-suite/<suite-id>` keys.

An action has two main properties:
* type (string) Identifier if the action.
* config (mixed) The data type is usually array, but it depends on the `type`.


### Action - replaceRecursive

Official PHP documentation:
[array_replace_recursive()](https://php.net/manual/en/function.array-replace-recursive.php)

* parents: array
* items: array

```json
{
    "require": {
        "a/b": "^1.0",
        "symfony/console": "^4.0",
        "symfony/process": "^4.0"
    },
    "extra": {
        "composer-suite": {
            "my-suite-01": [
                {
                    "type": " replaceRecursive",
                    "config": {
                        "parents": [],
                        "items": {
                            "require": {
                                "symfony/console": "^5.0",
                                "symfony/process": "^5.0"
                            }
                        }
                    }
                }
            ]
        }
    }
}
```

Result:
```json
{
    "require": {
        "a/b": "^1.0",
        "symfony/console": "^5.0",
        "symfony/process": "^5.0"
    },
    "extra": {}
}
```


### Action - unset

Removes the specified elements.

* parents: array

```json
{
    "name": "a/b",
    "foo": {
        "bar": "delete me"
    },
    "extra": {
        "composer-suite": {
            "my-suite-01": [
                {
                    "type": "unset",
                    "config": {
                        "parents": [
                            "foo",
                            "bar"
                        ]
                    }
                }
            ]
        }
    }
}
```

Result:
```json
{
    "name": "a/b",
    "foo": {},
    "extra": {}
}
```

In the "config.parents" array the last item can be an array:
```json
{
    "name": "a/b",
    "foo": {
        "a": "delete me 1",
        "b": "keep me",
        "c": "delete me 2"
    },
    "extra": {
        "composer-suite": {
            "my-suite-01": [
                {
                    "type": "unset",
                    "config": {
                        "parents": [
                            "foo",
                            ["a", "c"]
                        ]
                    }
                }
            ]
        }
    }
}
```

Result:
```json
{
    "name": "a/b",
    "foo": {
        "b": "keep me"
    },
    "extra": {}
}
```


### Action - preprend 

Adds new elements at the beginning of an array.

* parents: array
* items: array


### Action - append

Adds new elements at the end of an array. 

* parents: array
* items: array


### Action - insertBefore

Insert one or more "items". The last item in the "parents" is the reference
point.

* parents: array
* items: array


### Action - insertAfter

Insert one or more "items". The last item in the "parents" is the reference
point.

* parents: array
* items: array


## Commands

This Composer plugin defines only one command. Which is  
`composer suite:generate`


## Validate

You can check the status of the autogenerated files by running the following
command:  
`composer validate`  
If one of the autogenerated file is out-of-date, then the exit code will be
other than `0`.


## Links

1. [Environment variables - COMPOSER](https://getcomposer.org/doc/03-cli.md#composer)


## Other

1. `./bin/codecept _completion --generate-hook --program codecept | source
   /dev/stdin`
