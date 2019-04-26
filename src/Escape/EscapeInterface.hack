namespace Ytake\Dotenv\Escape;

<<__Sealed(ResolveName::class, ResolveValue::class)>>
interface EscapeInterface {

  public function resolve(
    string $name,
    string $value
  ): (string, string);
}
