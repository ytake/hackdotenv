<?hh // strict

namespace Ytake\Dotenv;

use namespace HH\Lib\Str;
use type Ytake\Dotenv\Exception\InvalidPathException;
use type Ytake\Dotenv\Sanitize\SanitizeName;
use type Ytake\Dotenv\Sanitize\SanitizeValue;

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
  }

  public function load(): void {
    $this->loadData();
  }

  public function safeLoad(): void {
    try {
      $this->loadData();
    } catch (InvalidPathException $e) {
      return;
    }
  }

  <<__Rx>>
  private function getFilePath(string $path, string $file): string {
    return Str\trim_right($path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $file;
  }

  protected function loadData(): void {
    $this->loader->load();
  }

  <<__Rx>>
  public function getEnvVarNames(): Vector<string> {
    return $this->loader->variableVec();
  }
}
