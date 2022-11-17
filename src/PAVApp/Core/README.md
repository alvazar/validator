 * Примеры использования класса.
 * 
 * Проверка является ли поле строкой.
 * 
 * $data = ['name' => 'Mike'];
 * $validator->check([
 *     'name' => 'is_string'
 * ], $data)->hasError(); // false - ошибок не найдено
 * 
 * 
 * Несколько проверок для поля.
 * 
 * $validator->check([
 *     'name' => ['is_string', 'is_numeric']
 * ], $data)->hasError(); // true, т.к. значение не число
 * 
 * 
 * Проверка на регулярное выражение.
 * Перед регулярным выражением нужно добавить ключевое слово regexp.
 * 
 * $validator->check([
 *     'name' => ['is_string', 'regexp/^[a-z]+$/i']
 * ], $data)->hasError(); // false
 * 
 * 
 * Также можно добавить анонимную функцию.
 * 
 * $validator->check([
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
 * $validator->check([
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
 * $validator->check([
 *     'name' => ['is_string', 'real_escape_string']
 * ], $data)->hasError(); // false, при этом $data теперь ['name' => 'Mike\"\"']