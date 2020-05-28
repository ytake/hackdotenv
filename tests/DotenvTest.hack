use type Ytake\Dotenv\Dotenv;
use type Facebook\HackTest\HackTest;
use type Ytake\Dotenv\Exception\InvalidPathException;

use function dirname;
use function getenv;
use function Facebook\FBExpect\expect;

final class DotenvTest extends HackTest {

  private ?string $dir;

  private Vector<string> $v = Vector{
    'FOO', 'BAR', 'INT', 'SPACED', 'NULL', 'IMMUTABLE'
  };

  <<__Override>>
  public async function beforeEachTestAsync(): Awaitable<void> {
    $this->dir = dirname(__DIR__) . '/tests/resources';
    $this->v->map(($v) ==> putenv($v));
  }

  public function testShouldThrowInvalidPathException(): void {
    expect(() ==> new Dotenv(__DIR__))
      ->toThrow(InvalidPathException::class);
  }

  public async function testDotenvLoadsEnvironmentVars(): Awaitable<void> {
    invariant($this->dir is string, "error");
    $dotenv = new Dotenv($this->dir);
    await $dotenv->loadAsync();
    expect(getenv('FOO'))->toBeSame('bar');
    expect(getenv('BAR'))->toBeSame('baz');
    expect(getenv('SPACED'))->toBeSame('with spaces');
    expect(getenv('NULL'))->toBeEmpty();
  }

  public async function testShouldNotOverwriteEnv(): Awaitable<void> {
    putenv('IMMUTABLE=true');
    invariant($this->dir is string, "error");
    $dotenv = new Dotenv($this->dir, 'imm.env');
    await $dotenv->loadAsync();
    expect(getenv('IMMUTABLE'))->toBeSame('true');
  }

  public async function testShouldGetEnvList(): Awaitable<void> {
    invariant($this->dir is string, "error");
    $dotenv = new Dotenv($this->dir);
    await $dotenv->loadAsync();
    expect($dotenv->getEnvVarNames() is vec<_>)->toBeTrue();
    expect($dotenv->getEnvVarNames())->toContain('FOO');
    expect($dotenv->getEnvVarNames())->toContain('BAR');
    expect($dotenv->getEnvVarNames())->toContain('INT');
    expect($dotenv->getEnvVarNames())->toContain('SPACED');
    expect($dotenv->getEnvVarNames())->toContain('NULL');
  }
}
