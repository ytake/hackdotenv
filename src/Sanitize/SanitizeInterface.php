<?hh // strict

namespace Ytake\Dotenv\Sanitize;

interface SanitizeInterface {

  public function sanitize(
    string $name,
    string $value
  ): (string, string);
}
