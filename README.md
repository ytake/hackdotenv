# Hack Dotenv

[![Build Status](https://travis-ci.org/ytake/hackdotenv.svg?branch=master)](https://travis-ci.org/ytake/hackdotenv)

**require HHVM >= ^3.28**

## Usage

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
