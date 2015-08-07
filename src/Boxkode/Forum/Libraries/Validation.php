<?php namespace Boxkode\Forum\Libraries;

use Input;
use Boxkode\Forum\Libraries\Alerts;
use Validator;

class Validation {

    public static function processValidationMessages($messages)
    {
        foreach ($messages as $message)
        {
            Alerts::add('danger', $message);
        }
    }

    public static function check($type = 'thread')
    {
        $rules = config('forum.preferences.validation_rules');
        $validator = Validator::make(Input::all(), $rules[$type]);

        if (!$validator->passes())
        {
            self::processValidationMessages($validator->messages()->all());
            return false;
        }

        return true;
    }

}
