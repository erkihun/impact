<?php

declare(strict_types=1);

namespace App\Actions\Settings;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

final readonly class StoreSettingAssetAction
{
    public function execute(string $settingKey, UploadedFile $file): string
    {
        $extension = strtolower($file->guessExtension() ?: $file->getClientOriginalExtension() ?: 'bin');
        $category = str($settingKey)->before('.')->slug()->toString();
        $directory = $category.'/'.str_replace(['.', '_'], '-', $settingKey).'/'.now('UTC')->format('Y/m');
        $filename = Str::uuid7().'.'.$extension;

        Storage::disk('public')->putFileAs($directory, $file, $filename, [
            'visibility' => 'public',
        ]);

        return '/storage/'.$directory.'/'.$filename;
    }
}
