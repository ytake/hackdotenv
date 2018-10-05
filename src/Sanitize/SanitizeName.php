<?hh // strict

namespace Ytake\Dotenv\Sanitize;

use namespace HH\Lib\Str;
use function str_replace;

class SanitizeName implements SanitizeInterface {

  <<__Rx>>
  public function sanitize(
    string $name,
    string $value
  ): (string, string) {
    $name = Str\trim(str_replace(['export ', '\'', '"'], '', $name));
    return tuple($name, $value);
  }
}
