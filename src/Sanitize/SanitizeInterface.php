<?hh // strict

namespace Ytake\Dotenv\Sanitize;

interface SanitizeInterface {

  <<__Rx>>
  public function sanitize(
    string $name,
    string $value
  ): (string, string);
}
