<?hh // strict

use namespace Ytake\Dotenv;
use type Facebook\HackTest\HackTest;

use function Facebook\FBExpect\expect;

final class EnvTest extends HackTest {
  private ?string $dir;
  <<__Override>>
  public async function beforeEachTestAsync(): Awaitable<void> {
    $this->dir = dirname(__DIR__) . '/tests/resources';
  }

  public function testShouldBeEnvValue(): void {
    invariant($this->dir is string, "error");
    $dotenv = new Dotenv\Dotenv($this->dir);
    $dotenv->load();
    expect(Dotenv\env('FOO'))->toBeSame('bar');
    expect(Dotenv\env('BAR'))->toBeSame('baz');
    expect(Dotenv\env('SPACED'))->toBeSame('with spaces');
    expect(Dotenv\env('NULL'))->toBeEmpty();
  }
}
