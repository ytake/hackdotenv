# Hack Dotenv

Loads environment variables from .env to getenv(). 

[![Build Status](https://travis-ci.org/ytake/hackdotenv.svg?branch=master)](https://travis-ci.org/ytake/hackdotenv)

[m1/Env](https://github.com/m1/Env) converted for Hack

**require HHVM >=3.28**

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
<?hh
use type Ytake\Dotenv\Loader;

$dotenv = new Dotenv($this->dir);
$dotenv->load();
```

```hack
<?hh
use namespace Ytake\Dotenv;

Dot\env('FOO');
```
