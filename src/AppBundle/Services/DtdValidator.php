<?php

namespace AppBundle\Services;

use DOMDocument;

class DtdValidator {

    private $errors;

    public function __construct() {
        $this->errors = array();
    }

    public function validationError($n, $message, $file, $line, $context) {
        $lxml = libxml_get_last_error();

        if ($lxml) {
            $this->errors[] = array(
                'message' => $lxml->message,
                'file' => $lxml->file,
                'line' => $lxml->line,
            );
        } else {
            $this->errors[] = array(
                'message' => $message,
                'file' => $file,
                'line' => $line,
            );
        }
    }

    public function validate(DOMDocument $dom, $clearErrors = true) {
        if($clearErrors) {
            $this->clearErrors();
        }
        if($dom->doctype === null) {
            return;
        }
        $oldHandler = set_error_handler(array($this, 'validationError'));
        $dom->validate();
        if ($oldHandler) {
            set_error_handler($oldHandler);
        }
    }

    public function hasErrors() {
        return count($this->errors) > 0;
    }

    public function countErrors() {
        return count($this->errors);
    }

    public function getErrors() {
        return $this->errors;
    }

    public function clearErrors() {
        $this->errors = array();
    }

}
