WP-CLI Plugin Active on Sites
===============================

A [WP-CLI](http://wp-cli.org/) command to list all sites in a Multisite network that have activated a given plugin.

## Examples / Usage

Find all sites where a specific plugin is active:

```shell
> wp plugin active-on-sites eu-cookie-law-widget

Checking each site  100% [==================================================] 0:02 / 0:03

Sites where eu-cookie-law-widget is active:
+---------+----------------------------------------+
| blog_id | url                                    |
+---------+----------------------------------------+
| 320     | http://2014.madrid.wordcamp.test/      |
| 371     | http://2014.paris.wordcamp.test/       |
| 413     | http://2015.london.wordcamp.test/      |
| 464     | http://2015.milano.wordcamp.test/      |
| 522     | http://2016.geneva.wordcamp.test/      |
| 571     | http://2016.belfast.wordcamp.test/     |
| 654     | http://2017.europe.wordcamp.test/      |
+---------+----------------------------------------+
```

Find all installed plugins that aren't active on any site:

```shell
> wp plugin active-on-sites --none

Checking each site  100% [==================================================] 0:02 / 0:03

Plugins not active on any site:
+---------------------------+------------------------------+
| slug                      | name                         |
+---------------------------+------------------------------+
| akismet                   | Akismet Anti-Spam            |
| wordpress-seo             | Yoast SEO                    |
+---------------------------+------------------------------+
```

See `wp help plugin active-on-sites` for details specifying fields, output format, etc.


## Installing

1. Ensure your `composer.json` has `composer/installers` and an `installer-paths` entry for `type:wordpress-plugin`. Most Composer-managed WordPress projects already have this.

    ```json
    {
        "require": {
            "composer/installers": "^2.0"
        },
        "extra": {
            "installer-paths": {
                "wp-content/plugins/{$name}/": ["type:wordpress-plugin"]
            }
        }
    }
    ```

2. Install the plugin:

    ```shell
    composer require iandunn/wp-cli-plugin-active-on-sites
    ```

3. Activate the plugin:

    ```shell
    wp plugin activate wp-cli-plugin-active-on-sites --network
    ```


## Local Development

```shell
git clone https://github.com/iandunn/wp-cli-plugin-active-on-sites
```

```shell
composer install
```

```shell
composer prepare-tests
```

```shell
composer test           # run all tests
composer test-rerun		# re-run only failed scenarios

composer test -- features/plugin-active-on-sites.feature        # run a single feature file
composer test -- features/plugin-active-on-sites.feature:42		# run a single scenario by line number
composer test -- --tags=@network                                # run only network-activation scenarios
```

When run inside a [LocalWP](https://localwp.com/) shell, `composer test` routes to `bin/behat-localwp`, which auto-detects the MySQL socket. Outside LocalWP (e.g. CI), it falls back to `run-behat-tests`.
