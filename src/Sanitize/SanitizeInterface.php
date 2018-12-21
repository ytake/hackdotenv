<?hh // strict

namespace Ytake\Dotenv\Sanitize;

<<__Sealed(SanitizeName::class, SanitizeValue::class)>>
interface SanitizeInterface {

  public function sanitize(
    string $name,
    string $value
  ): (string, string);
}
