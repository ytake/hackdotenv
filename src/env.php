<?hh // strict

namespace Ytake\Dotenv;

use function getenv;
use function strtolower;

function env(string $key, ?string $default = null): mixed {
  $value = getenv($key);
  if ($value === false) {
    return $default;
  }
  switch (strtolower($value)) {
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
