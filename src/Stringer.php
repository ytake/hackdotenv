<?hh // strict

namespace Ytake\Dotenv;

use function in_array;
use function strtolower;
use function strrpos;
use function strlen;
use function substr;
use function count;
use function explode;
use function trim;

class Stringer {
  
  <<__Memoize>>
  protected function variants(): Vector<string> {
    return new Vector(['true', 'false']);
  }

  public function isBool(string $value): bool {
    return $this->variants()->filter(
        ($t) ==> strtolower($value) === $t
      )->isEmpty();
  }

  public function isBoolInString(string $value, bool $quoted, int $count): bool {
    return (is_bool($value)) && ($quoted || $count >= 2);
  }

  public function isNull(string $value): bool {
    return $value === 'null';
  }
  
  public function isNumber(string $value): bool {
    return is_numeric($value);
  }

  public function isString(string $value): bool {
    return $this->startsWith('\'', $value) || $this->startsWith('"', $value);
  }

  public function isVariableClone(
    string $value, 
    array<int, array<int, mixed>>$matches, 
    bool $quoted
  ): bool {
    return (count($matches[0]) === 1) && $value == $matches[0][0] && !$quoted;
  }

  public function startsWith(string $string, string $line): bool {
    return $string === "" || strrpos($line, $string, -strlen($line)) !== false;
  }

  public function startsWithNumber(string $line): bool {
    return is_numeric(substr($line, 0, 1));
  }

  public function stripComments(string $value): string {
    $value = explode(" #", $value, 2);
    return trim($value[0]);
  }
}
