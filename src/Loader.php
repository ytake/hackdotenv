<?hh // strict

namespace Ytake\Dotenv;

use namespace HH\Lib\{Str, Vec};
use type Ytake\Dotenv\Exception\InvalidPathException;
use type Ytake\Dotenv\Sanitize\SanitizeName;
use type Ytake\Dotenv\Sanitize\SanitizeValue;

use function is_readable;
use function is_file;
use function sprintf;
use function ini_get;
use function ini_set;
use function file;
use function strpos;
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
    $this->ensure();
    $lines = $this->readFile($this->filePath);
    Vec\map(
      Vec\filter(
        $this->readFile($this->filePath),
        ($row) ==> !$this->isComment($row) && $this->isAssign($row)
      ),
      ($v) ==> $this->setEnvVariable($v)
    );
    return new ImmMap($lines);
  }

  protected function ensure(): void {
    if (!is_readable($this->filePath) || !is_file($this->filePath)) {
      throw new InvalidPathException(
        sprintf('Unable to read the environment file at %s.', $this->filePath)
      );
    }
  }

  <<__Rx>>
  protected function normalise(string $name, string $value): (string, string) {
    list($name, $value) = $this->processFilters($name, $value);
    return tuple($name, $this->resolveNestedVariables($value));
  }

  <<__Rx>>
  public function processFilters(string $name, string $value): (string, string) {
    list($name, $value) = $this->split($name, $value)
    |> $this->sn->sanitize($$[0], $$[1])
    |> $this->sv->sanitize($$[0], $$[1]);
    return tuple($name, $value);
  }

  protected function readFile(string $filePath): vec<string> {
    $autodetect = ini_get('auto_detect_line_endings');
    ini_set('auto_detect_line_endings', '1');
    $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    ini_set('auto_detect_line_endings', $autodetect);
    return vec($lines);
  }

  <<__Rx>>
  protected function isComment(string $line): bool {
    return Str\starts_with(Str\trim_left($line), '#');
  }

  <<__Rx>>
  protected function isAssign(string $line): bool {
    return strpos($line, '=') !== false;
  }

  <<__Rx>>
  protected function split(string $name, string $value): (string, string) {
    if (strpos($name, '=') !== false) {
      $a = Vec\map(
        Str\split($name, '=', 2),
        ($v) ==> Str\trim($v)
      );
      return tuple(strval($a[0]), strval($a[1]));
    }
    return tuple(strval($name), strval($value));
  }

  <<__Rx>>
  protected function resolveNestedVariables(string $value): string {
    if (strpos($value, '$') !== false) {
      $value = preg_replace_callback(
        '/\${([a-zA-Z0-9_.]+)}/',
        ($matchedPatterns) ==> {
          $nestedVariable = $this->getEnvVariable($matchedPatterns[1]);
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

  <<_Rx>>
  public function getEnvVariable(string $name): ?string {
    $value = getenv($name);
    return $value === false ? null : $value;
  }

  <<_Rx>>
  public function setEnvVariable(
    string $name,
    string $value = ''
  ): void {
    list($name, $value) = $this->normalise($name, $value);
    $this->vn->add($name);
    if ($this->imm && $this->getEnvVariable($name) !== null) {
      return;
    }
    $this->m->add(Pair{$name, $value});
    putenv("$name=$value");
  }

  public function clearEnvVariable(string $name): void {
    if ($this->imm) {
      return;
    }
    $this->m->remove($name);
    putenv($name);
  }


  public function variableVec(): Vector<string> {
    return $this->vn;
  }

  public function envMap(): Map<string, string> {
    return $this->m;
  }
}
