<?hh // strict

namespace Ytake\Dotenv;

use type Ytake\Dotenv\Exception\InvalidPathException;

use function is_string;
use function rtrim;

use const DIRECTORY_SEPARATOR;

class Dotenv {

  protected string $filePath;

  protected Loader $loader;

  public function __construct(
    string $path,
    string $file = '.env'
  ) {
    $this->filePath = $this->getFilePath($path, $file);
    $this->loader = new Loader($this->filePath, true);
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

  public function getEnvironmentVariableNames(): Vector<string> {
    return $this->loader->variableVec();
  }
}
