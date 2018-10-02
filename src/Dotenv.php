<?hh // strict

namespace Ytake\Dotenv;

use type Ytake\Dotenv\Exception\InvalidPathException;
use type Ytake\Dotenv\Sanitize\SanitizeName;
use type Ytake\Dotenv\Sanitize\SanitizeValue;

use function rtrim;

use const DIRECTORY_SEPARATOR;

<<__ConsistentConstruct>>
class Dotenv {

  protected Loader $loader;

  public function __construct(
    string $path,
    string $file = '.env'
  ) {
    $this->loader = new Loader(
      $this->getFilePath($path, $file),
      new SanitizeName(),
      new SanitizeValue()
    );
    $this->loader->setImmutable(true);
  }

  public function load(): ImmMap<int, string> {
    return $this->loadData();
  }

  public function safeLoad(): ImmMap<int, string> {
    try {
      return $this->loadData();
    } catch (InvalidPathException $e) {
      return new ImmMap([]);
    }
  }

  public function overload(): ImmMap<int, string> {
    return $this->loadData(true);
  }

  private function getFilePath(string $path, string $file): string {
    return rtrim($path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $file;
  }

  protected function loadData(
    bool $overload = false
  ): ImmMap<int, string> {
    return $this->loader->setImmutable(!$overload)->load();
  }

  public function getEnvVarNames(): Vector<string> {
    return $this->loader->variableVec();
  }
}
