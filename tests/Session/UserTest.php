<?php declare(strict_types=1);

namespace Nais\Device\Approval\Session;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(User::class)]
class UserTest extends TestCase
{
    public function testCanGetValues(): void
    {
        $user = new User('id', 'name');
        $this->assertSame('id', $user->getObjectId());
        $this->assertSame('name', $user->getName());
    }
}
