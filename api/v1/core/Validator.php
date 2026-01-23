<?php
/**
 * Classe Validator - Validation des données entrantes
 */
class Validator {

    private $errors = [];
    private $data = [];

    public function __construct($data) {
        $this->data = $data;
    }

    /**
     * Valide qu'un champ est requis
     */
    public function required($field, $message = null) {
        if (!isset($this->data[$field]) || trim($this->data[$field]) === '') {
            $this->errors[$field][] = $message ?? "Le champ $field est requis";
        }
        return $this;
    }

    /**
     * Valide qu'un champ est un email valide
     */
    public function email($field, $message = null) {
        if (isset($this->data[$field]) && !filter_var($this->data[$field], FILTER_VALIDATE_EMAIL)) {
            $this->errors[$field][] = $message ?? "Le champ $field doit être un email valide";
        }
        return $this;
    }

    /**
     * Valide la longueur minimale d'un champ
     */
    public function minLength($field, $min, $message = null) {
        if (isset($this->data[$field]) && strlen($this->data[$field]) < $min) {
            $this->errors[$field][] = $message ?? "Le champ $field doit contenir au moins $min caractères";
        }
        return $this;
    }

    /**
     * Valide la longueur maximale d'un champ
     */
    public function maxLength($field, $max, $message = null) {
        if (isset($this->data[$field]) && strlen($this->data[$field]) > $max) {
            $this->errors[$field][] = $message ?? "Le champ $field ne doit pas dépasser $max caractères";
        }
        return $this;
    }

    /**
     * Valide qu'un champ est numérique
     */
    public function numeric($field, $message = null) {
        if (isset($this->data[$field]) && !is_numeric($this->data[$field])) {
            $this->errors[$field][] = $message ?? "Le champ $field doit être numérique";
        }
        return $this;
    }

    /**
     * Valide qu'un champ est dans une liste de valeurs autorisées
     */
    public function in($field, $values, $message = null) {
        if (isset($this->data[$field]) && !in_array($this->data[$field], $values)) {
            $allowedValues = implode(', ', $values);
            $this->errors[$field][] = $message ?? "Le champ $field doit être l'une des valeurs suivantes: $allowedValues";
        }
        return $this;
    }

    /**
     * Valide qu'un champ correspond à un format de date MySQL (YYYYMMDDHHmmss)
     */
    public function mysqlDateTime($field, $message = null) {
        if (isset($this->data[$field])) {
            $value = $this->data[$field];
            if (!preg_match('/^\d{14}$/', $value)) {
                $this->errors[$field][] = $message ?? "Le champ $field doit être au format YYYYMMDDHHmmss";
            }
        }
        return $this;
    }

    /**
     * Valide un pseudonyme (alphanumerique + tirets/underscores)
     */
    public function username($field, $message = null) {
        if (isset($this->data[$field])) {
            if (!preg_match('/^[a-zA-Z0-9_-]+$/', $this->data[$field])) {
                $this->errors[$field][] = $message ?? "Le champ $field ne doit contenir que des lettres, chiffres, tirets et underscores";
            }
        }
        return $this;
    }

    /**
     * Sanitize une chaîne pour éviter XSS
     */
    public static function sanitize($value) {
        if (is_array($value)) {
            return array_map([self::class, 'sanitize'], $value);
        }
        return htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8');
    }

    /**
     * Vérifie si la validation a échoué
     */
    public function fails() {
        return !empty($this->errors);
    }

    /**
     * Récupère les erreurs de validation
     */
    public function getErrors() {
        return $this->errors;
    }

    /**
     * Récupère les données validées
     */
    public function getData() {
        return $this->data;
    }

    /**
     * Récupère une valeur spécifique des données
     */
    public function get($field, $default = null) {
        return $this->data[$field] ?? $default;
    }
}
