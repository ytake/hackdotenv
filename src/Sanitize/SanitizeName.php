<?hh // strict

namespace Ytake\Dotenv\Sanitize;

use function str_replace;
use function trim;

class SanitizeName implements SanitizeInterface {

  public function sanitize(
    string $name,
    string $value
  ): (string, string) {
    $name = trim(str_replace(['export ', '\'', '"'], '', $name));
    return tuple($name, $value);
  }
}
