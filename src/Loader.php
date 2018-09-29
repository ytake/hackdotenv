<?hh // strict

namespace Ytake\Dotenv;

use type Ytake\Dotenv\Exception\InvalidFileException;
use type Ytake\Dotenv\Exception\InvalidPathException;

use function is_readable;
use function is_file;
use function sprintf;
use function ltrim;
use function ini_get;
use function ini_set;
use function file;
use function strrpos;
use function strlen;
use function strpos;
use function explode;
use function trim;
use function strval;
use function preg_match;
use function preg_replace;
use function str_replace;
use function preg_replace_callback;
use function function_exists;
use function putenv;

use const FILE_IGNORE_NEW_LINES;
use const FILE_SKIP_EMPTY_LINES;

class Loader {

  protected Vector<string> $variableNames = Vector{};

  public function __construct(
    protected string $filePath,
    protected bool $immutable = false
  ) {}

  public function setImmutable(
    bool $immutable = false
  ): this {
    $this->immutable = $immutable;
    return $this;
  }

  public function getImmutable(): bool {
    return $this->immutable;
  }

  public function load(): ImmMap<int, string> {
    $this->ensureFileIsReadable();
    $filePath = $this->filePath;
    $lines = $this->readLinesFromFile($filePath);
    foreach ($lines as $line) {
      if (!$this->isComment($line) && $this->looksLikeSetter($line)) {
        $this->setEnvironmentVariable($line);
      }
    }
    return new ImmMap($lines);
  }

  protected function ensureFileIsReadable(): void {
    if (!is_readable($this->filePath) || !is_file($this->filePath)) {
      throw new InvalidPathException(
        sprintf('Unable to read the environment file at %s.', $this->filePath)
      );
    }
  }

  protected function normaliseEnvironmentVariable(string $name, string $value): (string, string) {
    list($name, $value) = $this->processFilters($name, $value);
    $value = $this->resolveNestedVariables($value);
    return tuple($name, $value);
  }

  public function processFilters(string $name, string $value): (string, string) {
    list($name, $value) = $this->splitCompoundStringIntoParts($name, $value);
    list($name, $value) = $this->sanitiseVariableName($name, $value);
    list($name, $value) = $this->sanitiseVariableValue($name, $value);

    return tuple($name, $value);
  }

  protected function readLinesFromFile(string $filePath): array<int, string> {
    // Read file into an array of lines with auto-detected line endings
    $autodetect = ini_get('auto_detect_line_endings');
    ini_set('auto_detect_line_endings', '1');
    $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    ini_set('auto_detect_line_endings', $autodetect);
    return $lines;
  }

  protected function isComment(string $line): bool {
    return $this->startsWith('#', ltrim($line));
  }

  public function startsWith(string $search, string $raw): bool {
    return $search === "" || strrpos($raw, $search, -strlen($raw)) !== false;
  }

  protected function looksLikeSetter(string $line): bool {
    return strpos($line, '=') !== false;
  }

  protected function splitCompoundStringIntoParts(string $name, string $value): (string, string) {
    if (strpos($name, '=') !== false) {
      $im = new ImmMap(explode('=', $name, 2));
      $a = $im->map(($v) ==> trim($v));
      $name = $a->firstKey();
      $value = $a->firstValue();
    }
    return tuple(strval($name), strval($value));
  }

  protected function sanitiseVariableValue(
    string $name,
    string $value
  ): (string, string) {
    $value = trim($value);
    if (!$value) {
      return tuple($name, $value);
    }
    if ($this->beginsWithAQuote($value)) {
      $quote = $value;
      $regexPattern = sprintf(
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
      $value = str_replace("\\$quote", $quote, $value);
      return tuple($name, str_replace('\\\\', '\\', $value));
    }
    $parts = explode(' #', $value, 2);
    $value = trim($parts[0]);
    if (preg_match('/\s+/', $value) > 0) {
      if (preg_match('/^#/', $value) > 0) {
        $value = '';
      }
      throw new InvalidFileException('Dotenv values containing spaces must be surrounded by quotes.');
    }
    return tuple($name, trim($value));
  }

  protected function resolveNestedVariables(string $value): string {
    if (strpos($value, '$') !== false) {
      $value = preg_replace_callback(
        '/\${([a-zA-Z0-9_.]+)}/',
        ($matchedPatterns) ==> {
          $nestedVariable = $this->getEnvironmentVariable($matchedPatterns[1]);
          if ($nestedVariable === null) {
            return $matchedPatterns[0];
          }
          return $nestedVariable;
        },
        $value
      );
    }
    return $value;
  }

  protected function sanitiseVariableName(
    string $name,
    string $value
  ): (string, string) {
    $name = trim(str_replace(['export ', '\'', '"'], '', $name));
    return tuple($name, $value);
  }

  protected function beginsWithAQuote(string $value): bool {
    return $value === '"' || $value === '\'';
  }

  public function getEnvironmentVariable(string $name): ?string {
    $value = \getenv($name);
    return $value === false ? null : $value;
  }

  public function setEnvironmentVariable(string $name, string $value = ''): void {
    list($name, $value) = $this->normaliseEnvironmentVariable($name, $value);
    $this->variableNames->add($name);

    if ($this->immutable && $this->getEnvironmentVariable($name) !== null) {
      return;
    }
    if (function_exists('putenv')) {
      putenv("$name=$value");
    }
  }

  public function clearEnvironmentVariable(string $name): void {
    if ($this->immutable) {
      return;
    }
    if (function_exists('putenv')) {
      putenv($name);
    }
  }

  public function variableVec(): Vector<string> {
    return $this->variableNames;
  }
}
