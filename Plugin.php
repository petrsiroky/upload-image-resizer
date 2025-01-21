<?php namespace Stheme\ImageResize;

use System\Classes\PluginBase;
use October\Rain\Database\Attach\File;
use Tinify\Tinify;
use Stheme\ImageResize\Models\Settings;

class Plugin extends PluginBase
{
    public function boot()
    {
        File::extend(function($model) {
            $model->bindEvent('model.beforeCreate', function() use ($model) {
                if ($model->isImage() && $model->isPublic()) {
                    $originalPath = $model->getLocalPath();

                    // GD resize for all images
                    list($width, $height) = getimagesize($originalPath);
                    $newWidth = Settings::get('image_width', 2000);
                    $ratio = $width / $height;
                    $newHeight = $newWidth / $ratio;
                    
                    $sourceImage = imagecreatefromstring(file_get_contents($originalPath));
                    $newImage = imagecreatetruecolor($newWidth, $newHeight);
                    
                    imagecopyresampled($newImage, $sourceImage, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
                    
                    $imageType = exif_imagetype($originalPath);
                    switch($imageType) {
                        case IMAGETYPE_JPEG:
                            imagejpeg($newImage, $originalPath, Settings::get('image_quality', 90));
                            break;
                        case IMAGETYPE_PNG:
                            $pngQuality = round((Settings::get('image_quality', 90) * 9) / 100);
                            imagepng($newImage, $originalPath, $pngQuality);
                            break;
                        case IMAGETYPE_GIF:
                            imagegif($newImage, $originalPath);
                            break;
                    }
                    
                    imagedestroy($sourceImage);
                    imagedestroy($newImage);

                    // Additional TinyPNG compression if enabled
                    if (Settings::get('use_tinypng') && Settings::get('tinypng_api_key')) {
                        Tinify::setKey(Settings::get('tinypng_api_key'));
                        $source = \Tinify\fromFile($originalPath);
                        $source->toFile($originalPath);
                    }

                    clearstatcache();
                    $model->file_size = filesize($originalPath);
                }
            });
        });
    }

    public function registerSettings()
    {
        return [
            'settings' => [
                'label' => 'Image Resize Settings',
                'description' => 'Manage API key and resize settings',
                'category' => 'Image Resize',
                'icon' => 'icon-image',
                'class' => 'Stheme\ImageResize\Models\Settings',
                'order' => 500,
                'keywords' => 'image resize tinypng webp'
            ]
        ];
    }
}
