<?hh // strict

namespace Ytake\Dotenv\Sanitize;

use type Ytake\Dotenv\Exception\InvalidFileException;

use namespace HH\Lib\{Str};
use function preg_replace;
use function preg_match;
use function strpos;
use function mb_substr;

class SanitizeValue implements SanitizeInterface {

  public function sanitize(
    string $name,
    string $value
  ): (string, string) {
    $value = Str\trim($value);
    if (!$value) {
      return tuple($name, $value);
    }
    if ($this->isQuote($value)) {
      $quote = $this->firstChar($value);
      $regexPattern = Str\format(
        '/^
        %s           # match a quote at the start of the value
          (              # capturing sub-pattern used
            (?:           # we do not need to capture this
              [^%s\\\\]* # any character other than a quote or backslash
              |\\\\\\\\    # or two backslashes together
              |\\\\%s    # or an escaped quote e.g \"
            )*            # as many characters that match the previous rules
          )              # end of the capturing sub-pattern
        %s           # and the closing quote
        .*$            # and discard any string after the closing quote
        /mx',
        $quote,
        $quote,
        $quote,
        $quote
      );
      $value = preg_replace($regexPattern, '$1', $value);
      $value = Str\replace($value, "\\$quote", $quote);
      return tuple($name, Str\replace($value, '\\\\', '\\'));
    }
    $p = Str\split($value, ' #', 2);
    $value = Str\trim($p[0]);
    if (preg_match('/\s+/', $value) > 0) {
      if (preg_match('/^#/', $value) === 0) {
        throw new InvalidFileException('values containing spaces must be surrounded by quotes.');
      }
      $value = '';
    }
    return tuple($name, Str\trim($value));
  }

  <<__Rx>>
  protected function isQuote(string $value): bool {
    return strpos($value, '"') === 0 || strpos($value, '\'');
  }

  <<__Rx>>
  protected function firstChar(string $value): string {
    return mb_substr($value, 0, 1);
  }
}
