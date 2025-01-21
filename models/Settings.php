<?php namespace Stheme\ImageResize\Models;

use Model;

class Settings extends Model
{
    public $implement = ['System.Behaviors.SettingsModel'];
    public $settingsCode = 'stheme_imageresize_settings';
    public $settingsFields = 'fields.yaml';
}
