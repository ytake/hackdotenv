<?hh // strict

namespace Ytake\Dotenv;

use type Ytake\Dotenv\Exception\InvalidPathException;
use type Ytake\Dotenv\Sanitize\SanitizeName;
use type Ytake\Dotenv\Sanitize\SanitizeValue;

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
use function getenv;
use function preg_replace_callback;
use function putenv;

use const FILE_IGNORE_NEW_LINES;
use const FILE_SKIP_EMPTY_LINES;

class Loader {

  protected Vector<string> $vn = Vector{};
  protected Map<string, string> $m = Map{};
  protected bool $imm = false;

  public function __construct(
    protected string $filePath,
    protected SanitizeName $sn,
    protected SanitizeValue $sv
  ) {}

  public function setImmutable(
    bool $immutable = false
  ): this {
    $this->imm = $immutable;
    return $this;
  }

  public function getImmutable(): bool {
    return $this->imm;
  }

  public function load(): ImmMap<int, string> {
    $this->ensureFileIsReadable();
    $lines = $this->readLinesFromFile($this->filePath);
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
    return tuple($name, $this->resolveNestedVariables($value));
  }

  public function processFilters(string $name, string $value): (string, string) {
    list($name, $value) = $this->split($name, $value);
    list($name, $value) = $this->sn->sanitize($name, $value);
    list($name, $value) = $this->sv->sanitize($name, $value);

    return tuple($name, $value);
  }

  protected function readLinesFromFile(string $filePath): array<int, string> {
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

  protected function split(string $name, string $value): (string, string) {
    if (strpos($name, '=') !== false) {
      $im = new ImmMap(explode('=', $name, 2));
      $a = $im->map(($v) ==> trim($v));
      $name = $a->get(0);
      $value = $a->get(1);
    }
    return tuple(strval($name), strval($value));
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

  public function getEnvironmentVariable(string $name): ?string {
    $value = getenv($name);
    return $value === false ? null : $value;
  }

  public function setEnvironmentVariable(string $name, string $value = ''): void {
    list($name, $value) = $this->normaliseEnvironmentVariable($name, $value);
    $this->vn->add($name);

    if ($this->imm && $this->getEnvironmentVariable($name) !== null) {
      return;
    }
    $this->m->add(Pair{$name, $value});
    putenv("$name=$value");
  }

  public function clearEnvironmentVariable(string $name): void {
    if ($this->imm) {
      return;
    }
    $this->m->remove($name);
    putenv($name);
  }

  <<__Memoize>>
  public function variableVec(): Vector<string> {
    return $this->vn;
  }

  <<__Memoize>>
  public function envMap(): Map<string, string> {
    return $this->m;
  }
}
