<?hh // strict

namespace Ytake\Dotenv;

use namespace HH\Lib\Str;

use function getenv;

<<__Rx>>
function env(string $key, ?string $default = null): mixed {
  $value = getenv($key);
  if ($value === false) {
    return $default;
  }
  switch (Str\lowercase($value)) {
    case 'true':
      return true;
    case 'false':
      return false;
    case 'empty':
      return '';
    case 'null':
      return null;
  }
  return $value;
}
