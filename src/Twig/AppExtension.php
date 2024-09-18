<?php

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class AppExtension extends AbstractExtension
{
    public function getFilters()
    {
        return [
            new TwigFilter('hash', [$this, 'hashFilter']),
        ];
    }

    public function hashFilter(string $value, string $algo = 'sha256'): string
    {
        return hash($algo, $value);
    }
}
