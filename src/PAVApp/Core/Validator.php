<?php
namespace PAVApp\Core;

/**
 * Примеры использования класса.
 * 
 * Проверка является ли поле строкой.
 * 
 * $data = ['name' => 'Mike'];
 * $Validator->check([
 *     'name' => 'is_string'
 * ], $data)->hasError(); // false - ошибок не найдено
 * 
 * 
 * Несколько проверок для поля.
 * 
 * $Validator->check([
 *     'name' => ['is_string', 'is_numeric']
 * ], $data)->hasError(); // true, т.к. значение не число
 * 
 * 
 * Проверка на регулярное выражение.
 * Перед регулярным выражением нужно добавить ключевое слово regexp.
 * 
 * $Validator->check([
 *     'name' => ['is_string', 'regexp/^[a-z]+$/i']
 * ], $data)->hasError(); // false
 * 
 * 
 * Также можно добавить анонимную функцию.
 * 
 * $Validator->check([
 *     'name' => ['is_string', function ($value) {
 *     return !empty($value);
 *   }]
 * ], $data)->hasError(); // false
 * 
 * 
 * Если функция проверки возвращает не булев тип, 
 * то её результат присваивается полю.
 * Например, мы проверяем, что поле является строкой 
 * и хотим обрезать значение до трёх символов.
 * 
 * $Validator->check([
 *     'name' => ['is_string', function ($value) {
 *     return mb_strlen($value) > 3 ? mb_substr($value, 0, 3) : $value;
 *   }]
 * ], $data)->hasError(); // false, при этом $data теперь ['name' => 'Mik']
 * 
 * 
 * Для того, чтобы экранировать значение поля
 * есть ключевое слово real_escape_string 
 * (вызывается метод mysqli::real_escape_string)
 * 
 * $data = ['name' => 'Mike""'];
 * $Validator->check([
 *     'name' => ['is_string', 'real_escape_string']
 * ], $data)->hasError(); // false, при этом $data теперь ['name' => 'Mike\"\"']
 */

/**
 * Класс-хелпер для валидации полей.
 */
class Validator
{
    /**
     * Список ошибок после проверки.
     * @var array
     */
    private $errors = [];

    /**
     * Проверяет значения полей.
     * @param array $checkRules
     * @param array $data - ссылка на массив.
     * 
     * @return array
     */
    public function check(array $checkRules, array &$data): self
    {
        $this->clear();
        foreach ($checkRules as $fieldName => $check) {
            if (is_array($check)) {
                foreach ($check as $checkItem) {
                    $this->checkValue($fieldName, $checkItem, $data);
                    if ($this->hasError()) {
                        break;
                    }
                }
            } else {
                $this->checkValue($fieldName, $check, $data);
            }
        }

        return $this;
    }

    /**
     * Проверяет значение поля при помощи callback-функции или регулярного выражения.
     * @param mixed $name
     * @param mixed $check
     * @param mixed $data
     * @param mixed $errors
     * 
     * @return void
     */
    private function checkValue(string $name, $check, array &$data): self
    {
        // Обязательное поле
        $isRequired = false;
        /*
        if ($name[0] === '*') {
            $name = mb_substr($name, 1);
            $isRequired = true;
        }
        */

        //
        if (!array_key_exists($name, $data)) {
            if ($isRequired) {
                $this->errors[] = $name;
            }
            return $this;
        // если значение равно NULL, то не делаем проверок
        } elseif (is_null($data[$name])) {
            return $this;
        }
        $value = $data[$name];
        
        $checkResult = true;
        if ($check !== true) {

            // Есть ли отметка, что поле может быть множественным
            $isMultiple = false;
            if (
                is_string($check) 
                && mb_substr($check, 0, 9) === 'multiple:'
            ) {
                $isMultiple = true;
                $check = mb_substr($check, 9); // убираем multiple:
            }

            // Если тип проверки регулярное выражение
            $isRegexp = false;
            if (is_string($check) && mb_substr($check, 0, 6) === 'regexp') {
                $isRegexp = true;
                $check = mb_substr($check, 6); // убираем regexp
            }

            // Если поле множественное
            if ($isMultiple && is_array($value)) {
                if (count($value) === 0) {
                    $checkResult = false;
                } else {
                    foreach ($value as $k => $val) {
                        // Проверка на регулярное выражение
                        if ($isRegexp) {
                            $checkResult = preg_match($check, $val) === 1;
                        
                        // Экранирование строки
                        } elseif ($check === 'real_escape_string') {
                            $checkResult = $this->db->real_escape_string($val);

                        // Проверка через callback функцию
                        } else {
                            $checkResult = $check($val);
                        }

                        if ($checkResult === false) {
                            break;
                        }

                        // если результат проверки не булев тип, то модифицируем значение
                        if (!is_bool($checkResult)) {
                            $value[$k] = $checkResult;
                            $data[$name] = $value;
                        }
                    }
                }

            // Проверка на регулярное выражение
            } elseif ($isRegexp) {
                $checkResult = preg_match($check, $value) === 1;
            
            // Экранирование строки
            } elseif ($check === 'real_escape_string') {
                $checkResult = $this->db->real_escape_string($value);

            // Проверка через callback функцию
            } else {
                $checkResult = $check($value);
            }

            // если результат проверки не булев тип, то модифицируем значение
            if (!is_bool($checkResult)) {
                $data[$name] = $checkResult;
            }
        }

        // Если поле не прошло проверку, то сохраняем ошибку.
        if ($checkResult === false) {
            $this->errors[] = $name;
        }

        return $this;
    }

    /**Сообщает была ли ошибка после проверки.
     * @return bool
     */
    public function hasError(): bool
    {
        return count($this->errors) > 0;
    }

    /**Возвращает список ошибок.
     * @return array
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**Сбрасывает состояние объекта.
     * @return Validator
     */
    public function clear(): self
    {
        $this->errors = [];
        return $this;
    }
}