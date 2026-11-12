<?php

declare(strict_types=1);

namespace adApiWpIntegration;

use adApiWpIntegration\Tests\AppTest;

function add_filter($hook, $callback)
{
    AppTest::$filters[$hook] = $callback;
}

function add_action($hook, $callback, $priority)
{
    AppTest::$actions[$hook] = [$callback, $priority];
}
