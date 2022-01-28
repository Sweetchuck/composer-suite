# Composer Suites

[![CircleCI](https://circleci.com/gh/Sweetchuck/composer-suite/tree/1.x.svg?style=svg)](https://circleci.com/gh/Sweetchuck/composer-suite/?branch=1.x)
[![codecov](https://codecov.io/gh/Sweetchuck/composer-suite/branch/1.x/graph/badge.svg?token=OXlFUvh6AY)](https://app.codecov.io/gh/Sweetchuck/composer-suite/branch/1.x)

Generates multiple variations of the original `composer.json`

1. You have to define the differences in the
   composer.json#/extra/composer-suite, see the examples below.
2. Next step is to generate the alternative composer.json files by running the
   following command: \
   `composer -vv suite:generate`
3. Activate one of the alternative composer.json file by setting the
   [COMPOSER environment variable].
   1. `export COMPOSER='composer.my-suite-01.json'`
   2. `composer update --lock`

Other benefit is that, if there is any relative path in the `composer.json` – 
for example under the `#/repositories/FOO/url` or anywhere under the `#/extra` –
then those paths will work with the alternative composer.*.json files as well.


## Example composer.json
```json
{
    "require": {
        "symfony/console": "^4.0 || ^5.0",
        "symfony/process": "^4.0 || ^5.0"
    },
    "require-dev": {
        "phpunit/phpunit": "^8.0"
    },
    "scripts": {
        "test": "phpunit"
    },
    "extra": {
        "composer-suite": {
            "symfony4": {
                "description": "Make sure Symfony 4.x will be used.",
                "actions": [
                    {
                        "type": "replaceRecursive",
                        "config": {
                            "items": {
                                "require": {
                                    "symfony/console": "^4.0",
                                    "symfony/process": "^4.0"
                                }
                            }
                        }
                    }
                ]
            },
            "symfony5": {
                "description": "Make sure Symfony 5.x will be used.",
                "actions": [
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
                    }
                ]
            }
        }
    }
}
```

Run: `composer suite:generate` \
The generated files: \
**composer.symfony4.json**
```json
{
    "require": {
        "symfony/console": "^4.0",
        "symfony/process": "^4.0"
    },
    "require-dev": {
        "phpunit/phpunit": "^8.0"
    },
    "scripts": {
        "test": "phpunit"
    },
    "extra": {}
}
```

**composer.symfony5.json**
```json
{
    "require": {
        "symfony/console": "^5.0",
        "symfony/process": "^5.0"
    },
    "require-dev": {
        "phpunit/phpunit": "^8.0"
    },
    "scripts": {
        "test": "phpunit"
    },
    "extra": {}
}
```

**Then**
```bash
unset COMPOSER
composer update --lock
composer suite:generate

export COMPOSER='composer.symfony4.json'
composer update
composer run test

export COMPOSER='composer.symfony5.json'
composer update
composer run test

unset COMPOSER
```


## Suites

You can have as many suites as many you want.  
In the example above there is only one (my-suite-01), but there can be more than
one.


## Actions

You can define different array manipulation actions under the
`extra/composer-suite/<suite-id>` keys.

An action has two main properties:
* type (string) Identifier of the action.
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
            "my-suite-01": {
                "actions": {
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
            }
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
            "my-suite-01": {
                "actions": {
                    "type": "unset",
                    "config": {
                        "parents": [
                            "foo",
                            "bar"
                        ]
                    }
                }
            }
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
            "my-suite-01": {
                "actions": {
                    "type": "unset",
                    "config": {
                        "parents": [
                            "foo",
                            [
                                "a",
                                "c"
                            ]
                        ]
                    }
                }
            }
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


### Action - prepend

Adds new elements at the beginning of an array.

* parents: array
* items: array

```json
{
    "repositories": {
        "old/p1": {}
    },
    "extra": {
        "composer-suite": {
            "local": {
                "actions": [
                    {
                        "type": "prepend",
                        "config": {
                            "parents": [
                                "repositories"
                            ],
                            "items": {
                                "new/p1": {},
                                "new/p2": {}
                            }
                        }
                    }
                ]
            }
        }
    }
}
```

Result:

```json
{
    "repositories": {
        "new/p1": {},
        "new/p2": {},
        "old/p1": {}
    },
    "extra": {}
}
```


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

1. [COMPOSER environment variable]


## Other

1. `"$(composer config bin-dir)/codecept" _completion --generate-hook --program codecept | source /dev/stdin`

---

[COMPOSER environment variable]: https://getcomposer.org/doc/03-cli.md#composer
