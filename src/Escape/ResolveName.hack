namespace Ytake\Dotenv\Escape;

use namespace HH\Lib\Str;

class ResolveName implements EscapeInterface {

  protected dict<string, string> $replacements = dict[
    'export ' => '',
    '\'' => '',
    '"' => ''
  ];

  public function resolve(
    string $name,
    string $value
  ): (string, string) {
    $name = Str\trim(
      Str\replace_every($name, $this->replacements)
    );
    return tuple($name, $value);
  }
}
