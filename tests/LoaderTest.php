<?hh // strict

use type Ytake\Dotenv\Loader;
use type Facebook\HackTest\HackTest;
use type Ytake\Dotenv\Sanitize\SanitizeName;
use type Ytake\Dotenv\Sanitize\SanitizeValue;

use function dirname;
use function getenv;
use function Facebook\FBExpect\expect;

final class LoaderTest extends HackTest {

  private ?Loader $immutableLoader;

  private ?Loader $mutableLoader;

  protected Map<string, string> $keyVal = Map{};

  <<__Override>>
  public async function beforeEachTestAsync(): Awaitable<void> {
    $folder = dirname(__DIR__) . '/resources';
    $this->keyVal(true);
    $this->mutableLoader = $this->muLoader($folder);
    $this->immutableLoader = $this->immLoader($folder);
  }

  private function muLoader(string $folder): Loader {
    return new Loader($folder, new SanitizeName(), new SanitizeValue());
  }

  private function immLoader(string $folder): Loader {
    $l = new Loader($folder, new SanitizeName(), new SanitizeValue());
    $l->setImmutable(true);
    return $l;
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

  public function testMutableLoaderSetUnsetImmutable(): void {
    $loader = $this->mutableLoader;
    invariant($loader instanceof Loader, 'error');
    $immutable = $loader->getImmutable();
    $loader->setImmutable(true);
    $loader->setImmutable(!$immutable);
    expect($loader->getImmutable())->toBeSame(!$immutable);
    $loader->setImmutable($immutable);
    expect($loader->getImmutable())->toBeSame($immutable);
  }

  public function testMutableLoaderClearsEnvironmentVars(): void {
    $loader = $this->mutableLoader;
    invariant($loader instanceof Loader, 'error');
    $k = $this->key();
    $v = $this->value();
    if ($k is string && $v is string) {
      $loader->setEnvironmentVariable($k, $v);
      $loader->clearEnvironmentVariable($k);
      expect($loader->getEnvironmentVariable($k))->toBeNull();
      expect(getenv($this->key()))->toBeFalse();
      expect($loader->variableVec())->toBeInstanceOf(Vector::class);
      expect($loader->variableVec())->toNotBeSame(0);
    }
  }

  public function testImmutableLoaderSetUnsetImmutable(): void {
    $loader = $this->mutableLoader;
    invariant($loader instanceof Loader, 'error');
    $immutable = $loader->getImmutable();
    $immLoader = $this->immutableLoader;
    invariant($immLoader instanceof Loader, 'error');
    $immLoader->setImmutable(!$immutable);
    expect($immLoader->getImmutable())->toBeSame(!$immutable);
    $immLoader->setImmutable($immutable);
    expect($immLoader->getImmutable())->toBeSame($immutable);
  }

  public function testImmutableLoaderCannotClearEnvironmentVars(): void {
    $immLoader = $this->immutableLoader;
    invariant($immLoader instanceof Loader, 'error');
    $k = $this->key();
    $v = $this->value();
    if ($k is string && $v is string) {
      $immLoader->setEnvironmentVariable($k, $v);
      $immLoader->clearEnvironmentVariable($k);
      expect($immLoader->getEnvironmentVariable($k))->toBeSame($v);
      expect(getenv($k))->toBeSame($v);
      expect($immLoader->variableVec())->toBeInstanceOf(Vector::class);
      expect($immLoader->variableVec())->toNotBeSame(0);
    }
  }
}
