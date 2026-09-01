<?php

namespace App\Data;

final readonly class ImportedTitleData
{
    public function __construct(
        public string $title,
        public string $contentType,
        public ?string $coverUrl,
        public ?string $description,
        public string $sourceUrl,
        public string $sourceWebsite,
        public ?string $latestChapter,
        /** @var array<string, string> */
        public array $chapterUrls,
    ) {}
}
