<?php
// ============================================================================
// UBICACIÓN: C:/xampp/htdocs/gestor-notas/helpers/Validator.php
// DESCRIPCIÓN: Validación de datos de entrada
// ============================================================================

class Validator {
    
    private $errors = [];
    
    /**
     * Validar email
     * @param string $email
     * @param string $fieldName
     * @return self
     */
    public function email($email, $fieldName = 'Email') {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->errors[$fieldName] = "$fieldName no es válido";
        }
        return $this;
    }
    
    /**
     * Validar campo requerido
     * @param mixed $value
     * @param string $fieldName
     * @return self
     */
    public function required($value, $fieldName = 'Campo') {
        if (empty($value)) {
            $this->errors[$fieldName] = "$fieldName es requerido";
        }
        return $this;
    }
    
    /**
     * Validar longitud mínima
     * @param string $value
     * @param int $min
     * @param string $fieldName
     * @return self
     */
    public function minLength($value, $min, $fieldName = 'Campo') {
        if (strlen($value) < $min) {
            $this->errors[$fieldName] = "$fieldName debe tener al menos $min caracteres";
        }
        return $this;
    }
    
    /**
     * Validar longitud máxima
     * @param string $value
     * @param int $max
     * @param string $fieldName
     * @return self
     */
    public function maxLength($value, $max, $fieldName = 'Campo') {
        if (strlen($value) > $max) {
            $this->errors[$fieldName] = "$fieldName no debe exceder $max caracteres";
        }
        return $this;
    }
    
    /**
     * Validar que dos campos coincidan
     * @param string $value1
     * @param string $value2
     * @param string $fieldName
     * @return self
     */
    public function match($value1, $value2, $fieldName = 'Los campos') {
        if ($value1 !== $value2) {
            $this->errors[$fieldName] = "$fieldName no coinciden";
        }
        return $this;
    }
    
    /**
     * Validar patrón regex
     * @param string $value
     * @param string $pattern
     * @param string $fieldName
     * @param string $message
     * @return self
     */
    public function pattern($value, $pattern, $fieldName = 'Campo', $message = null) {
        if (!preg_match($pattern, $value)) {
            $this->errors[$fieldName] = $message ?? "$fieldName no cumple con el formato requerido";
        }
        return $this;
    }
    
    /**
     * Validar fortaleza de contraseña
     * @param string $password
     * @return self
     */
    public function strongPassword($password) {
        if (strlen($password) < 8) {
            $this->errors['password'] = "La contraseña debe tener al menos 8 caracteres";
        } elseif (!preg_match('/[A-Z]/', $password)) {
            $this->errors['password'] = "La contraseña debe contener al menos una mayúscula";
        } elseif (!preg_match('/[a-z]/', $password)) {
            $this->errors['password'] = "La contraseña debe contener al menos una minúscula";
        } elseif (!preg_match('/[0-9]/', $password)) {
            $this->errors['password'] = "La contraseña debe contener al menos un número";
        }
        return $this;
    }
    
    /**
     * Verificar si hay errores
     * @return bool
     */
    public function fails() {
        return !empty($this->errors);
    }
    
    /**
     * Obtener todos los errores
     * @return array
     */
    public function getErrors() {
        return $this->errors;
    }
    
    /**
     * Obtener primer error
     * @return string|null
     */
    public function getFirstError() {
        return !empty($this->errors) ? reset($this->errors) : null;
    }
}
?>