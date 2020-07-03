namespace Ytake\Dotenv;

use type HH\Lib\OS\NotFoundException;
use namespace HH\Lib\Str;
use namespace Ytake\Dotenv\Escape;
use namespace HH\Lib\File;
use const DIRECTORY_SEPARATOR;

<<__ConsistentConstruct>>
class Dotenv {

  protected Loader $loader;

  public function __construct(
    string $path,
    string $file = '.env'
  ) {
    $this->loader = new Loader(
      $this->fileOpen($path, $file),
      new Escape\ResolveName(),
      new Escape\ResolveValue()
    );
  }

  public async function loadAsync(): Awaitable<void> {
    await $this->loadDataAsync();
  }

  public async function safeLoadAsync(): Awaitable<void> {
    try {
      await $this->loadDataAsync();
    } catch (Exception\InvalidPathException $e) {
      return;
    }
  }

  private function fileOpen(
    string $path,
    string $file
  ): File\CloseableReadHandle {
    try {
      return File\open_read_only(
        Str\trim_right($path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $file
      );
    } catch(NotFoundException $e) {
      throw new Exception\InvalidPathException($e->getMessage(), $e->getErrno(), $e);
    }
  }

  protected async function loadDataAsync(): Awaitable<void> {
    await $this->loader->loadAsync();
  }

  <<__Rx>>
  public function getEnvVarNames(): vec<string> {
    return $this->loader->variableVec();
  }
}
