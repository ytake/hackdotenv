<?hh // strict

use type Ytake\Dotenv\Loader;
use type Facebook\HackTest\HackTest;
use namespace Ytake\Dotenv\Escape;
use namespace HH\Lib\Experimental\Filesystem;
use function dirname;
use function getenv;
use function Facebook\FBExpect\expect;

final class LoaderTest extends HackTest {

  protected Map<string, string> $keyVal = Map{};

  private function muLoader(string $folder): Loader {
    return new Loader(
      Filesystem\open_read_only_non_disposable($folder),
      new Escape\ResolveName(),
      new Escape\ResolveValue()
    );
  }

  protected function keyVal(bool $reset = false): Map<string, string> {
    if ($this->keyVal->count() !== 0 || $reset) {
      $this->keyVal = new Map([uniqid() => uniqid()]);
    }
    return $this->keyVal;
  }

  protected function key(): ?string {
    $keyVal = $this->keyVal();
    return $keyVal->firstKey();
  }

  protected function value(): ?string {
    $keyVal = $this->keyVal();
    return $keyVal->firstValue();
  }

  public function testMutableLoaderClearsEnvironmentVars(): void {
    $folder = dirname(__DIR__) . '/tests/resources';
    $this->keyVal(true);
    $loader = $this->muLoader($folder);
    $k = $this->key();
    $v = $this->value();
    if ($k is string && $v is string) {
      expect($loader->getEnvVariable($k))->toBeNull();
      /* HH_IGNORE_ERROR[4110] */
      expect(getenv($this->key()))->toBeFalse();
      expect($loader->variableVec() is vec<_>)->toBeTrue();
      expect($loader->variableVec())->toNotBeSame(0);
    }
  }
}
