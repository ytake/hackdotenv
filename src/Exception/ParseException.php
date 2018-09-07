<?hh // strict

namespace Ytake\Dotenv\Exception;

use function is_null;
use function sprintf;

class ParseException extends \RuntimeException {

  public function __construct(
    string $message, 
    ?string $line = null, 
    ?int $lineNum = null
  ) {
    parent::__construct($this->createMessage($message, $line, $lineNum));
  }

  private function createMessage(
    string $message, 
    ?string $line, 
    ?int $lineNum
  ): string {
    if (!is_null($line)) {
      $message .= sprintf(" near %s", $line);
    }
    if (!is_null($lineNum)) {
      $message .= sprintf(" at line %d", $lineNum);
    }
    return $message;
  }
}
