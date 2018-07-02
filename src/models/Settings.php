<?php
/**
 * @link      https://wesleyluyten.com
 * @copyright Copyright (c) Wesley Luyten
 * @license   https://git.io/craft-polls-license
 */

namespace luwes\polls\models;

use craft\base\Model;

class Settings extends Model
{
    public $requireLogin = false;

    public function rules()
    {
        return [

        ];
    }
}
