<?php

declare(strict_types=1);

namespace adApiWpIntegration\Tests {
    use adApiWpIntegration\App;
    use adApiWpIntegration\Input;
    use PHPUnit\Framework\TestCase;
    use WpService\Implementations\NativeWpService;

    class AppTest extends TestCase
    {
        public static array $filters = [];
        public static array $actions = [];

        /**
         * @runInSeparateProcess
         * @preserveGlobalState disabled
         */
        public function testNativeWpServiceCanInitializeTheApp(): void
        {
            require __DIR__ . '/fixtures/hooks.php';

            if (!defined('AD_INTEGRATION_URL')) {
                define('AD_INTEGRATION_URL', 'https://example.com');
            }

            $app = new App(new Input(), new NativeWpService());

            static::assertSame('__return_false', self::$filters['send_password_change_email']);
            static::assertSame('__return_false', self::$filters['send_email_change_email']);
            static::assertSame([[$app, 'hijackLogin'], 20], self::$actions['wp_authenticate']);
        }
    }
}
