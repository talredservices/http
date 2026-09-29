<?php

declare(strict_types=1);

namespace Zolta\Tests\Unit\Compatibility;

use PHPUnit\Framework\TestCase;
use Talred\Http\Exceptions\ActionNotAllowedException;
use Talred\Http\Request\BaseRequest;
use Talred\Http\Request\Contracts\RequestPort;
use Talred\Http\Request\Traits\HandlesRequestValues;
use Talred\Http\Router\Attributes\Route;

final class TalredNamespaceCompatibilityTest extends TestCase
{
    public function test_talred_class_alias_uses_the_existing_zolta_implementation(): void
    {
        self::assertTrue(class_exists(BaseRequest::class));
        self::assertTrue(is_a(BaseRequest::class, \Zolta\Http\Request\BaseRequest::class, true));
    }

    public function test_talred_interface_alias_uses_the_existing_zolta_contract(): void
    {
        self::assertTrue(interface_exists(RequestPort::class));
        self::assertTrue(is_a(RequestPort::class, \Zolta\Http\Request\Contracts\RequestPort::class, true));
    }

    public function test_talred_trait_alias_uses_the_existing_zolta_trait(): void
    {
        self::assertTrue(trait_exists(HandlesRequestValues::class));
        self::assertTrue(trait_exists(\Zolta\Http\Request\Traits\HandlesRequestValues::class));
    }

    public function test_talred_attribute_alias_uses_the_existing_zolta_attribute(): void
    {
        self::assertTrue(class_exists(Route::class));
        self::assertTrue(is_a(Route::class, \Zolta\Http\Router\Attributes\Route::class, true));
    }

    public function test_talred_exception_alias_uses_the_existing_zolta_exception(): void
    {
        self::assertTrue(class_exists(ActionNotAllowedException::class));
        self::assertTrue(is_a(ActionNotAllowedException::class, \Zolta\Http\Exceptions\ActionNotAllowedException::class, true));
    }
}
