<?hh // strict

namespace Ytake\Dotenv\Sanitize;

use type Ytake\Dotenv\Exception\InvalidFileException;

use function explode;
use function preg_replace;
use function preg_match;
use function strpos;
use function trim;
use function mb_substr;
use function sprintf;
use function str_replace;

class SanitizeValue implements SanitizeInterface {

  public function sanitize(
    string $name,
    string $value
  ): (string, string) {
    $value = trim($value);
    if (!$value) {
      return tuple($name, $value);
    }
    if ($this->isQuote($value)) {
      $quote = $this->firstChar($value);
      $reg = sprintf(
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
      |> str_replace("\\$quote", $quote, $$)
      |> str_replace('\\\\', '\\', $$);
      return tuple($name, $value);
    }
    $p = explode(' #', $value, 2);
    $value = trim($p[0]);
    if (preg_match('/\s+/', $value) > 0) {
      if (preg_match('/^#/', $value) === 0) {
        throw new InvalidFileException('values containing spaces must be surrounded by quotes.');
      }
      $value = '';
    }
    return tuple($name, trim($value));
  }

  protected function isQuote(string $value): bool {
    return strpos($value, '"') === 0 || strpos($value, '\'');
  }

  protected function firstChar(string $value): string {
    return mb_substr($value, 0, 1);
  }
}
