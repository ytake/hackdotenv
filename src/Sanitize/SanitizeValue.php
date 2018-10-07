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
      $reg = Str\format(
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
      $value = preg_replace($reg, '$1', $value)
      |> Str\replace($$, "\\$quote", $quote)
      |> Str\replace($$, '\\\\', '\\');
      return tuple($name, $value);
    }
    $p = Str\split($value, ' #', 2);
    $value = Str\trim($p[0]);
    if (preg_match('/\s+/', $value) > 0) {
      if (preg_match('/^#/', $value) === 0) {
        throw new InvalidFileException('values containing spaces must be surrounded by quotes.');
      }
      $value = '';
    }
    return tuple($name, $value);
  }

  <<__Rx>>
  protected function isQuote(string $value): bool {
    return strpos($value, '"') === 0 || strpos($value, '\'');
  }

  <<__Rx>>
  protected function firstChar(string $value): string {
    /* HH_IGNORE_ERROR[4200] Non-reactive: unused optional byref argument */
    return mb_substr($value, 0, 1);
  }
}
