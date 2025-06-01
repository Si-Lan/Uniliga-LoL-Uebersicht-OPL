<?php

use App\UI\Components\Navigation\Header;
use App\UI\Enums\HeaderType;
use App\UI\Page\PageMeta;

$pageMeta = new PageMeta('Admin-Panel', bodyClass: 'admin');

echo new Header(HeaderType::ADMIN);
