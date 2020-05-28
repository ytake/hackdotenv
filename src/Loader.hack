namespace Ytake\Dotenv;

use namespace HH\Lib\{File, Str, Vec};
use namespace Ytake\Dotenv\Escape;

use function strval;
use function getenv;
use function preg_replace_callback;
use function putenv;

use const FILE_IGNORE_NEW_LINES;
use const FILE_SKIP_EMPTY_LINES;

class Loader {

  protected vec<string> $vn = vec[];
  protected dict<string, string> $m = dict[];

  public function __construct(
    protected File\CloseableReadHandle $readHandle,
    protected Escape\ResolveName $sn,
    protected Escape\ResolveValue $sv
  ) {}

  public async function loadAsync(): Awaitable<void> {
    $rows = Vec\filter(
        await $this->readAsync($this->readHandle),
        ($row) ==> !$this->isComment($row) && $this->isAssign($row)
    );
    foreach($rows as $row) {
      $this->setEnvVariable($row);
    }
  }

  protected function normalise(string $name, string $value): (string, string) {
    list($name, $value) = $this->filters($name, $value);
    return tuple($name, $this->resolveNestedVariables($value));
  }

  public function filters(string $name, string $value): (string, string) {
    list($name, $value) = $this->split($name, $value)
    |> $this->sn->resolve($$[0], $$[1])
    |> $this->sv->resolve($$[0], $$[1]);
    return tuple($name, $value);
  }

  protected async function readAsync(
    File\CloseableReadHandle $readHandle
  ): Awaitable<vec<string>> {
    $vm = Vec\map(Str\split(await $readHandle->readAsync(), "\n"), ($v) ==> Str\trim($v));
    return Vec\filter($vm, ($k) ==> Str\length($k) !== 0);
  }

  <<__Rx>>
  protected function isComment(string $line): bool {
    return Str\starts_with(Str\trim_left($line), '#');
  }

  <<__Rx>>
  protected function isAssign(string $line): bool {
    return Str\search($line, '=') is nonnull;
  }

  protected function split(string $name, string $value): (string, string) {
    if ($this->isAssign($name)) {
      $a = Vec\map(
        Str\split($name, '=', 2),
        ($v) ==> Str\trim($v)
      );
      return tuple(strval($a[0]), strval($a[1]));
    }
    return tuple(strval($name), strval($value));
  }

  protected function resolveNestedVariables(string $value): string {
    $count = null;
    if (Str\search($value, '$') is nonnull) {
      $value = preg_replace_callback(
        '/\${([a-zA-Z0-9_.]+)}/',
        ($matchedPatterns) ==> {
          $nestedVariable = $this->getEnvVariable($matchedPatterns[1]);
          if ($nestedVariable === null) {
            return $matchedPatterns[0];
          }
          return $nestedVariable;
        },
        $value,
        -1,
        inout $count
      );
    }
    return $value;
  }

  public function getEnvVariable(string $name): ?string {
    $value = getenv($name);
    return $value === false ? null : $value;
  }

  protected function setEnvVariable(
    string $name,
    string $value = ''
  ): void {
    list($name, $value) = $this->normalise($name, $value);
    $this->vn[] = $name;
    if ($this->getEnvVariable($name) !== null) {
      return;
    }
    $this->m[$name] = $value;
    putenv($name."=".$value);
  }

  <<__Rx>>
  public function variableVec(): vec<string> {
    return $this->vn;
  }

  <<__Rx>>
  public function envDict(): dict<string, string> {
    return $this->m;
  }
}
