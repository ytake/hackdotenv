<?hh // strict

use type Ytake\Dotenv\Dotenv;
use type Facebook\HackTest\HackTest;
use type Ytake\Dotenv\Exception\InvalidPathException;

use function dirname;
use function getenv;
use function Facebook\FBExpect\expect;

final class DotenvTest extends HackTest {

  private ?string $dir;

  <<__Override>>
  public async function beforeEachTestAsync(): Awaitable<void> {
    $this->dir = dirname(__DIR__) . '/tests/resources';
  }

  <<ExpectedException(InvalidPathException::class)>>
  public function testShouldThrowInvalidPathException(): void {
    $dotenv = new Dotenv(__DIR__);
    $dotenv->load();
  }

  public function testShouldBeEmptyMapIfThrowInvalidPathException(): void{
    $dotenv = new Dotenv(__DIR__);
    expect($dotenv->safeLoad())->toBeInstanceOf(ImmMap::class);
    expect($dotenv->safeLoad()->count())->toBeSame(0);
  }

  public function testDotenvLoadsEnvironmentVars(): void {
    invariant($this->dir is string, "error");
    $dotenv = new Dotenv($this->dir);
    $dotenv->load();
    expect(getenv('FOO'))->toBeSame('bar');
    expect(getenv('BAR'))->toBeSame('baz');
    expect(getenv('SPACED'))->toBeSame('with spaces');
    expect(getenv('NULL'))->toBeEmpty();
  }

  public function testShouldNotOverwriteEnv(): void {
    putenv('IMMUTABLE=true');
    invariant($this->dir is string, "error");
    $dotenv = new Dotenv($this->dir, 'imm.env');
    $dotenv->load();
    expect(getenv('IMMUTABLE'))->toBeSame('true');
  }

  public function testShouldLoadAfterOverload(): void {
    putenv('IMMUTABLE=true');
    invariant($this->dir is string, "error");
    $dotenv = new Dotenv($this->dir, 'imm.env');
    $dotenv->overload();
    expect(getenv('IMMUTABLE'))->toBeSame('false');
    putenv('IMMUTABLE=true');
    $dotenv->load();
    expect(getenv('IMMUTABLE'))->toBeSame('true');
  }

  public function testShouldOverloadAfterLoad(): void {
    putenv('IMMUTABLE=true');
    invariant($this->dir is string, "error");
    $dotenv = new Dotenv($this->dir, 'imm.env');
    $dotenv->load();
    expect(getenv('IMMUTABLE'))->toBeSame('true');
    putenv('IMMUTABLE=true');
    $dotenv->overload();
    expect(getenv('IMMUTABLE'))->toBeSame('false');
  }

  public function testShouldGetEnvList(): void {
    invariant($this->dir is string, "error");
    $dotenv = new Dotenv($this->dir);
    $dotenv->load();
    expect($dotenv->getEnvVarNames())->toBeInstanceOf(Vector::class);
    expect($dotenv->getEnvVarNames())->toContain('FOO');
    expect($dotenv->getEnvVarNames())->toContain('BAR');
    expect($dotenv->getEnvVarNames())->toContain('INT');
    expect($dotenv->getEnvVarNames())->toContain('SPACED');
    expect($dotenv->getEnvVarNames())->toContain('NULL');
  }
}