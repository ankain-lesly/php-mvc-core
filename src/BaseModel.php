<?php

/**
 * User: Dev_Lee
 * Date: 6/29/2023
 * Time: 6:00 AM
 */

namespace app\models;

use app\database\DataAccess;
use app\database\Database;

/**
 * Class BaseModel
 *
 * @author  Ankain Lesly <leeleslyank@gmail.com>
 * @package app\-> Models
 */
class BaseModel
{
  const RULE_REQUIRED = 'required';
  const RULE_EMAIL = 'email';
  const RULE_MIN = 'min';
  const RULE_MAX = 'max';
  const RULE_MATCH = 'match';
  const RULE_UNIQUE = 'unique';

  public array $errors = [];
  public \PDO $PDO;
  public DataAccess $DataAccess;

  public function __construct()
  {
    $DB = new Database();
    $this->PDO = $DB->connect();

    $this->DataAccess = new DataAccess($this->PDO);
    /**
     * Database
     * connect method
     */
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
      self::RULE_UNIQUE => 'Record with with this {field} already exists',
    ];
  }

  public function validate(array $update_data = [])
  {
    $update_attrs = array_keys($update_data);

    foreach ($this->rules() as $attribute => $rules) {

      if ($update_attrs && !in_array($attribute, $update_attrs)) continue;

      $value = $this->{$attribute};
      foreach ($rules as $rule) {
        $ruleName = $rule;
        if (!is_string($rule)) {
          $ruleName = $rule[0];
        }
        if ($ruleName === self::RULE_REQUIRED && !$value) {
          $this->addErrorByRule($attribute, self::RULE_REQUIRED);
        }
        if ($ruleName === self::RULE_EMAIL && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
          $this->addErrorByRule($attribute, self::RULE_EMAIL);
        }
        if ($ruleName === self::RULE_MIN && strlen($value) < $rule['min']) {
          $this->addErrorByRule($attribute, self::RULE_MIN, ['min' => $rule['min']]);
        }
        if ($ruleName === self::RULE_MAX && strlen($value) > $rule['max']) {
          $this->addErrorByRule($attribute, self::RULE_MAX);
        }
        if ($ruleName === self::RULE_MATCH && $value !== $this->{$rule['match']}) {
          $this->addErrorByRule($attribute, self::RULE_MATCH, ['match' => $rule['match']]);
        }
        if ($ruleName === self::RULE_UNIQUE) {
          $className = $rule['class'];
          $uniqueAttr = $rule['attribute'] ?? $attribute;
          $tableName = $className::tableName();
          $db = $this->PDO;
          $statement = $db->prepare("SELECT * FROM $tableName WHERE $uniqueAttr = :$uniqueAttr");
          $statement->bindValue(":$uniqueAttr", $value);
          $statement->execute();
          $record = $statement->fetchObject();
          if ($record) {
            $this->addErrorByRule($attribute, self::RULE_UNIQUE);
          }
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
    $this->errors['errors'][$attribute]['value'] = $this->{$attribute};
  }
  public function addErrorMessage(string $message)
  {
    $this->errors['message'] = $message;
    return false;
  }

  // public function hasError($attribute)
  // {
  //   return $this->errors[$attribute] ?? false;
  // }

  public function getErrors()
  {
    return $this->errors ? $this->errors : false;
  }
  // public function getFirstError($attribute)
  // {
  //   $errors = $this->errors[$attribute] ?? [];
  //   return $errors[0] ?? '';
  // }
}
