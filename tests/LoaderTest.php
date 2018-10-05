<?hh // strict

use type Ytake\Dotenv\Loader;
use type Facebook\HackTest\HackTest;
use type Ytake\Dotenv\Sanitize\SanitizeName;
use type Ytake\Dotenv\Sanitize\SanitizeValue;

use function dirname;
use function getenv;
use function Facebook\FBExpect\expect;

final class LoaderTest extends HackTest {

  private ?Loader $mutableLoader;

  protected Map<string, string> $keyVal = Map{};

  <<__Override>>
  public async function beforeEachTestAsync(): Awaitable<void> {
    $folder = dirname(__DIR__) . '/tests/resources';
    $this->keyVal(true);
    $this->mutableLoader = $this->muLoader($folder);
  }

  private function muLoader(string $folder): Loader {
    return new Loader($folder, new SanitizeName(), new SanitizeValue());
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
    $loader = $this->mutableLoader;
    invariant($loader instanceof Loader, 'error');
    $k = $this->key();
    $v = $this->value();
    if ($k is string && $v is string) {
      expect($loader->getEnvVariable($k))->toBeNull();
      expect(getenv($this->key()))->toBeFalse();
      expect($loader->variableVec())->toBeInstanceOf(Vector::class);
      expect($loader->variableVec())->toNotBeSame(0);
    }
  }
}
