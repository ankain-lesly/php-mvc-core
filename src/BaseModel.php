<?php

/**
 * User: Dev_Lee
 * Date: 06/29/2023 - Time: 6:00 AM
 * Updated: 10/03/2023 - Time: 11:54 PM
 */


namespace Devlee\PHPMVCCore;

use Devlee\PHPMVCCore\DB\Database;

/**
 * @author  Ankain Lesly <leeleslyank@gmail.com>
 * @package  Devlee\PHPMVCCore\BaseModel
 */

class BaseModel
{
  const RULE_REQUIRED = 'required';
  const RULE_EMAIL = 'email';
  const RULE_MIN = 'min';
  const RULE_MAX = 'max';
  const RULE_MATCH = 'match';
  const RULE_UNIQUE = 'unique';

  // Pending...
  const RULE_REGEX = 'regex';

  public array $errors = [];
  public \PDO $PDO;

  public function __construct()
  {
    $DB = new Database();
    $this->PDO = $DB->connect();
  }

  public function loadData($data)
  {
    foreach ($data as $key => $value) {
      if (property_exists($this, $key)) {
        $this->{$key} = $value;
      }
    }
  }

  public function attributes()
  {
    return [];
  }

  public function labels()
  {
    return [];
  }

  public function getLabel($attribute)
  {
    return $this->labels()[$attribute] ?? $attribute;
  }

  public function rules()
  {
    return [];
  }

  public function errorMessages()
  {
    return [
      self::RULE_REQUIRED => 'This field is required',
      self::RULE_EMAIL => 'This field must be valid email address',
      self::RULE_MIN => 'Min length of this field must be {min}',
      self::RULE_MAX => 'Max length of this field must be {max}',
      self::RULE_MATCH => 'This field must be the same as {match}',
      self::RULE_UNIQUE => '{field} is already taken',
    ];
  }

  public function validate(array $update_data = [])
  {
    $update_attrs = array_keys($update_data);

    foreach ($this->rules() as $attribute => $rules) {

      if ($update_attrs && !in_array($attribute, $update_attrs)) continue;

      // Check rule attr if exists()
      if (!property_exists($this, $attribute)) {
        // TODO:
        die("Property does not exists: " . $attribute);
        // exit("Property does not exist: " . $attribute);
      }

      $value = $this->{$attribute};
      foreach (explode(",", $rules) as $rule) {
        // De complex rules()
        $message = trim(explode('>>', $rule)[1] ?? false);
        $rule = explode('>>', $rule)[0];
        $ruleName = trim(explode('|', $rule)[0]);
        $ruleNameValue = trim(explode('|', $rule)[1] ?? false);

        if ($ruleName === self::RULE_REQUIRED && !$value) {
          if ($message) {
            $this->addError($attribute, $message);
          } else {
            $this->addErrorByRule($attribute, self::RULE_REQUIRED);
          }
        }
        if ($ruleName === self::RULE_EMAIL && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
          if ($message) {
            $this->addError($attribute, $message);
          } else {
            $this->addErrorByRule($attribute, self::RULE_EMAIL);
          }
        }
        if ($ruleName === self::RULE_MIN && strlen($value) < $ruleNameValue) {
          if ($message) {
            $this->addError($attribute, $message);
          } else {
            $this->addErrorByRule($attribute, self::RULE_MIN, ['min' => $ruleNameValue]);
          }
        }
        if ($ruleName === self::RULE_MAX && strlen($value) > $ruleNameValue) {
          if ($message) {
            $this->addError($attribute, $message);
          } else {
            $this->addErrorByRule($attribute, self::RULE_MAX, ['max' => $ruleNameValue]);
          }
        }
        if ($ruleName === self::RULE_MATCH && $value !== $this->{$ruleNameValue}) {
          if ($message) {
            $this->addError($attribute, $message);
          } else {
            $this->addErrorByRule($attribute, self::RULE_MATCH, ['match' => $ruleNameValue]);
          }
        }
        if ($ruleName === self::RULE_UNIQUE) {
          if ($update_data) return;
          $uniqueAttr = $rule['attribute'] ?? $attribute;
          $where = [$uniqueAttr => $value];
          $record = $this->findOne($where, [$attribute]);

          if ($record && $message) {
            $this->addError($attribute, $message);
          } elseif ($record) {
            $this->addErrorByRule($attribute, self::RULE_UNIQUE);
          }
        }

        // CHecking invalid rulename
        if (
          $ruleName !== self::RULE_EMAIL &&
          $ruleName !== self::RULE_MATCH &&
          $ruleName !== self::RULE_MAX &&
          $ruleName !== self::RULE_MIN &&
          $ruleName !== self::RULE_REQUIRED &&
          $ruleName !== self::RULE_UNIQUE
        ) {
          // TODO:
          exit("Invalid validation schema: <b>$ruleName</b> in $attribute");
        }
      }
    }
    return empty($this->errors);
  }

  public function getErrorMessage($rule)
  {
    return $this->errorMessages()[$rule];
  }

  protected function addErrorByRule(string $attribute, string $rule, $params = [])
  {
    $params['field'] ??= $attribute;
    $errorMessage = $this->getErrorMessage($rule);
    foreach ($params as $key => $value) {
      $errorMessage = str_replace("{{$key}}", $value, $errorMessage);
    }

    $this->errors['errors'][$attribute]['errors'][] = $errorMessage;
    $this->errors['errors'][$attribute]['value'] = $this->{$attribute};
    $this->addErrorMessage('Error, Please check fields and try again');
  }

  public function addError(string $attribute, string $message)
  {
    $this->errors['errors'][$attribute]['errors'][] = $message;
    $this->errors['errors'][$attribute]['value'] = $this->{$attribute} ?? false;
  }

  public function addErrorMessage(string $message)
  {
    $this->errors['message'] = $message;
    return false;
  }

  public function getErrors()
  {
    return $this->errors ? $this->errors : false;
  }

  // Hash Strings
  public static function hashString(string $string)
  {
    return password_hash($string, PASSWORD_DEFAULT);
  }
  public static function verifyHashed(string $string, string $hashedString)
  {
    return password_verify($string, $hashedString);
  }
}
