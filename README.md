WP-CLI Plugin Active on Sites
===============================

A [WP-CLI](http://wp-cli.org/) command to list all sites in a Multisite network that have activated a given plugin.

## Installing

`wp package install iandunn/wp-cli-plugin-active-on-sites`

## Usage

`wp plugin active-on-sites <plugin_slug>`

## Example

```shell
> wp plugin active-on-sites eu-cookie-law-widget

Checking each site  100% [==================================================] 0:02 / 0:03

Sites where eu-cookie-law-widget is active:
+---------+----------------------------------------+
| Site ID | Site URL                               |
+---------+----------------------------------------+
| 320     | 2014.madrid.wordcamp.dev/              |
| 371     | 2014.paris.wordcamp.dev/               |
| 413     | 2015.london.wordcamp.dev/              |
| 464     | 2015.milano.wordcamp.dev/              |
| 522     | 2016.geneva.wordcamp.dev/              |
| 571     | 2016.belfast.wordcamp.dev/             |
| 654     | 2017.europe.wordcamp.dev/              |
+---------+----------------------------------------+
```
