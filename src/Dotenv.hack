namespace Ytake\Dotenv;

use type HH\Lib\Experimental\OS\NotFoundException;
use namespace HH\Lib\Str;
use namespace Ytake\Dotenv\Escape;
use namespace HH\Lib\Experimental\File;
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

  public function load(): void {
    $this->loadData();
  }

  public function safeLoad(): void {
    try {
      $this->loadData();
    } catch (Exception\InvalidPathException $e) {
      return;
    }
  }

  private function fileOpen(
    string $path,
    string $file
  ): File\NonDisposableReadHandle {
    try {
      return File\open_read_only_nd(
        Str\trim_right($path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $file
      );
    } catch(NotFoundException $e) {
      throw new Exception\InvalidPathException($e->getMessage(), $e->getCode(), $e);
    }
  }

  protected function loadData(): void {
    $this->loader->load();
  }

  <<__Rx>>
  public function getEnvVarNames(): vec<string> {
    return $this->loader->variableVec();
  }
}
