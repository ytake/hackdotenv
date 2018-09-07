<?hh // strict

namespace Ytake\Dotenv\Parser;

use type Ytake\Dotenv\Parser;

abstract class AbstractParser {

  public function __construct(
    protected Parser $parser
  ) {}
}
