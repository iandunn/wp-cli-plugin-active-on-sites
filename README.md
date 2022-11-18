WP-CLI Plugin Active on Sites
===============================

A [WP-CLI](http://wp-cli.org/) command to list all sites in a Multisite network that have activated a given plugin.

## Installing

`wp package install iandunn/wp-cli-plugin-active-on-sites`

## Usage

`wp plugin active-on-sites <plugin_slug>`

See `wp help plugin active-on-sites` for details specifying fields, output format, etc.

## Example

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
