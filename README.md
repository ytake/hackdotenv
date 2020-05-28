# Hack Dotenv

Loads environment variables from .env to getenv(). 

[![Build Status](https://travis-ci.org/ytake/hackdotenv.svg?branch=master)](https://travis-ci.org/ytake/hackdotenv)

[vlucas/phpdotenv](https://github.com/vlucas/phpdotenv) converted for Hack

**require HHVM >=4.20**

## Install

```
$ hhvm $(which composer) require ytake/hackdotenv
```

## Usage

Your application configuration to a .env file in the root of your project.

```
FOO=bar
BAR=baz
```

You can then load .env in your application.

```hack
use type Ytake\Dotenv\Loader;

$dotenv = new Dotenv($this->dir);
await $dotenv->loadAsync();
```

```hack
use namespace Ytake\Dotenv;

Dot\env('FOO');
```
