### Introduction

This repository contains a PHP SDK that gives you access to the [Heroes of Newerth][]-InGame-API.
The SDK is tested successfully in PHP >= 5.3.1.
I used [fsockopen][] instead of cURL to prevent further compatibility problems.

### Requirements

* PHP >= 5.3.1
* php.ini - [allow_url_fopen][] = 1

### Known Issues

* Currently there is no known issue.

### Usage & Examples

Please see the [examples provided][].

### Show Your Support

If you find a Bug feel free to fill out a new [issue][]!

[issue]: https://github.com/riyuk/hon-sdk/issues
[examples provided]: https://github.com/riyuk/hon-sdk/blob/master/sample.php
[fsockopen]: http://www.php.net/fsockopen
[allow_url_fopen]: http://php.net/manual/en/filesystem.configuration.php#ini.allow-url-fopen
[Heroes of Newerth]: http://www.heroesofnewerth.com/