<?php

/**
 * User: Dev_Lee
 * Date: 06/29/2023 - Time: 6:00 AM
 * Updated: 10/03/2023 - Time: 9:30 PM
 * Updated: 10/06/2023 - Time: 10:00 AM
 */

namespace Devlee\WakerORM\Services;

use Devlee\WakerORM\DB\Model;
use Devlee\WakerORM\Exceptions\ValidationException;

/**
 * @author  Ankain Lesly <leeleslyank@gmail.com>
 * @package  Waker-ORM
 */

class ObjectSchema
{
  /**
   * @property
   * 
   */
  protected const RULE_REQUIRED = 'required';
  protected const RULE_EMAIL = 'email';
  protected const RULE_MIN = 'min';
  protected const RULE_MAX = 'max';
  protected const RULE_SAME = 'same';
  protected const RULE_UNIQUE = 'unique';

  const RULE_MATCH = 'match';


  /**
   * Validation errors array
   * @property array $errors
   */
  private array $errors = [];

  /**
   * Validation rules array
   * @property $rules
   */
  private array $rules = [];


  private array $validate_data;
  /**
   * An Instance of a model class
   * @property Model $model
   * 
   */
  public function __construct(public Model $model)
  {
  }


  /**
   * Set Validation rules. 
   * @method setValidationRules
   * 
   * @param array $rules An array of data keys and validation options as values
   * @return void
   */
  public function setValidationRules(array $rules)
  {
    $this->rules = $rules;
  }

  /**
   * Get Validation rules
   * @method getRules
   * 
   * @return array
   */
  public function getRules(): array
  {
    return $this->rules;
  }

  /**
   * Returns messages|text from validation rules
   * @method errorMessages
   * 
   * @return array
   */
  public function errorMessages(): array
  {
    return [
      self::RULE_REQUIRED => '{field} is required',
      self::RULE_EMAIL => '{field} must be valid email address',
      self::RULE_MIN => 'Min length of {field} must be {min}',
      self::RULE_MAX => 'Max length of {field} must be {max}',
      self::RULE_SAME => '{field} must be the same as {match}',
      self::RULE_UNIQUE => '{field} is already taken',
      self::RULE_MATCH => '{field} is invalid',
    ];
  }

  /**
   * Validates data based on object validation rules 
   * @method $validate
   * 
   * Contains data to be validated
   * @param $data 
   * 
   */
  public function validate(array $data = [])
  {
    $this->validate_data = $data;

    $data_attrs = array_keys($this->validate_data);

    /**
     * @var Model $model
     */
    foreach ($this->getRules() as $attribute => $rules) {

      if (!in_array($attribute, $data_attrs)) continue;

      if (!property_exists($this->model, $attribute) && !in_array($attribute, $data_attrs)) {
        throw new ValidationException("Unknown property: " . $attribute);
      }

      // $value = $this->{$attribute};
      $value = $this->validate_data[$attribute];
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
        if ($ruleName === self::RULE_SAME && $value !== $this->validate_data[$ruleNameValue]) {
          if ($message) {
            $this->addError($attribute, $message);
          } else {
            $this->addErrorByRule($attribute, self::RULE_SAME, ['match' => $ruleNameValue]);
          }
        }
        if ($ruleName === self::RULE_UNIQUE) {
          // if ($this->validate_data) return;
          $uniqueAttr = $rule['attribute'] ?? $attribute;
          $where = [$uniqueAttr => $value];
          $record = $this->model->findOne($where, [$attribute]);

          if ($record && $message) {
            $this->addError($attribute, $message);
          } elseif ($record) {
            $this->addErrorByRule($attribute, self::RULE_UNIQUE);
          }
        }

        // CHecking invalid rule names 
        if (
          $ruleName !== self::RULE_EMAIL &&
          $ruleName !== self::RULE_SAME &&
          $ruleName !== self::RULE_MAX &&
          $ruleName !== self::RULE_MIN &&
          $ruleName !== self::RULE_REQUIRED &&
          $ruleName !== self::RULE_UNIQUE
        ) {
          $rules = implode(', ', array_keys($this->errorMessages()));
          throw new ValidationException(
            "Invalid validation schema rule: <b>$ruleName</b> in $attribute",
            context: ['Try' => $rules]
          );
        }
      }
    }

    // return empty($this->errors);
    return $this->errors;
  }

  /**
   * Get error message based on rule
   * @method errorMessages
   * 
   */
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
    $this->errors['errors'][$attribute]['value'] = $this->validate_data[$attribute];
    $this->addErrorMessage('Error, Please check fields and try again');
  }

  public function addError(string $attribute, string $message)
  {
    $this->errors['errors'][$attribute]['errors'][] = $message;
    $this->errors['errors'][$attribute]['value'] = $this->validate_data[$attribute] ?? false;
  }

  public function addErrorMessage(string $message)
  {
    $this->errors['message'] = $message;
  }

  /**
   * Returns validation errors
   * @method getErrors
   * 
   */
  public function getErrors()
  {
    return $this->errors ? $this->errors : false;
  }

  /**
   * Check if Validation returned any errors
   * @method hasErrors
   * 
   */
  public function hasErrors()
  {
    return empty($this->errors);
  }
}
