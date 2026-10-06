<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Seo\StaticImageOptimizer;
use Illuminate\Console\Command;
use Throwable;

final class SeoImagesOptimizeCommand extends Command
{
    protected $signature = 'seo:images-optimize';

    protected $description = 'Generate responsive WebP/AVIF derivatives and social cards for design images in resources/images/source';

    public function handle(StaticImageOptimizer $optimizer): int
    {
        ini_set('memory_limit', '1024M');

        try {
            $manifest = $optimizer->optimizeAll();
        } catch (Throwable $exception) {
            $this->components->error($exception->getMessage());

            return self::FAILURE;
        }

        $budget = (int) config('impact.seo.image_budgets_kb.hero', 500) * 1024;
        $rows = [];
        foreach ($manifest as $name => $entry) {
            $rows[] = [$name, 'source (private)', $entry['width'].'×'.$entry['height'], $this->kb((int) $entry['source_bytes']), '—'];
            foreach ([...$entry['variants'], $entry['social']] as $variant) {
                $rows[] = [
                    '',
                    $variant['path'],
                    $variant['width'].'×'.$variant['height'],
                    $this->kb((int) $variant['bytes']),
                    $variant['bytes'] > $budget ? 'over hero budget' : 'ok',
                ];
            }
        }
        $this->table(['Image', 'File', 'Size', 'Bytes', 'Budget'], $rows);
        $this->components->info(count($manifest).' source image(s) optimized.');

        return self::SUCCESS;
    }

    private function kb(int $bytes): string
    {
        return number_format($bytes / 1024, 1).' KB';
    }
}
