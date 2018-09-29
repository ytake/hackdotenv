<?hh // strict

use type Ytake\Dotenv\Loader;
use type PHPUnit\Framework\TestCase;

use function dirname;
use function getenv;

final class LoaderTest extends TestCase {

  private ?Loader $immutableLoader;

  private ?Loader $mutableLoader;

  protected Map<string, string> $keyVal = Map{};

  <<__Override>>
  protected function setUp(): void {
    $folder = dirname(__DIR__) . '/resources';
    $this->keyVal(true);
    $this->mutableLoader = new Loader($folder);
    $this->immutableLoader = new Loader($folder, true);
  }

  private function muLoader(string $folder): Loader {
    return new Loader($folder);
  }

private function immLoader(string $folder): Loader {
  return new Loader($folder, true);
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
    $this->assertSame(!$immutable, $loader->getImmutable());
    $loader->setImmutable($immutable);
    $this->assertSame($immutable, $loader->getImmutable());
  }

  public function testMutableLoaderClearsEnvironmentVars(): void {
    $loader = $this->mutableLoader;
    invariant($loader instanceof Loader, 'error');
    $k = $this->key();
    $v = $this->value();
    if ($k is string && $v is string) {
      $loader->setEnvironmentVariable($k, $v);
      $loader->clearEnvironmentVariable($k);
      $this->assertSame(null, $loader->getEnvironmentVariable($k));
      $this->assertSame(false, getenv($this->key()));
      $this->assertInstanceOf(Vector::class, $loader->variableVec());
      $this->assertNotCount(0, $loader->variableVec());
    }
  }

  public function testImmutableLoaderSetUnsetImmutable(): void {
    $loader = $this->mutableLoader;
    invariant($loader instanceof Loader, 'error');
    $immutable = $loader->getImmutable();
    $immLoader = $this->immutableLoader;
    invariant($immLoader instanceof Loader, 'error');
    $immLoader->setImmutable(!$immutable);
    $this->assertSame(!$immutable, $immLoader->getImmutable());
    $immLoader->setImmutable($immutable);
    $this->assertSame($immutable, $immLoader->getImmutable());
  }

  public function testImmutableLoaderCannotClearEnvironmentVars(): void {
    $immLoader = $this->immutableLoader;
    invariant($immLoader instanceof Loader, 'error');
    $k = $this->key();
    $v = $this->value();
    if ($k is string && $v is string) {
      $immLoader->setEnvironmentVariable($k, $v);
      $immLoader->clearEnvironmentVariable($k);
      $this->assertSame($v, $immLoader->getEnvironmentVariable($k));
      $this->assertSame($v, getenv($k));
      $this->assertInstanceOf(Vector::class, $immLoader->variableVec());
      $this->assertNotCount(0, $immLoader->variableVec()); 
    }
  }
}
