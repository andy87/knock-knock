<?php declare(strict_types=1);

namespace andy87\knock_knock\tests\core;

use PHPUnit\Framework\TestCase;
use andy87\knock_knock\core\Operator;

/** Проверяет поддержку защищённого Singleton-слота существующих наследников. */
final class SingletonCompatibilityTest extends TestCase
{
    /** Проверяет выбор заранее настроенного экземпляра, включая повторную настройку.
     * @return void
     * @throws \Exception При неожиданной ошибке создания клиента.
     */
    public function testPreconfiguredProtectedInstanceTakesPrecedence(): void
    {
        $cached = ConfiguredSingletonOperator::getInstance('https://cached.example.com');
        $configured = ConfiguredSingletonOperator::configure('https://configured.example.com');
        self::assertNotSame($cached, $configured);
        self::assertSame($configured, ConfiguredSingletonOperator::getInstance());
        self::assertSame($configured, ConfiguredSingletonOperator::getInstance('https://ignored.example.com'));

        $replacement = ConfiguredSingletonOperator::configure('https://replacement.example.com');
        self::assertSame($replacement, ConfiguredSingletonOperator::getInstance());
    }

    /** Проверяет, что общий защищённый слот не возвращает объект другого класса.
     * @return void
     * @throws \Exception При неожиданной ошибке создания клиента.
     */
    public function testInheritedSlotDoesNotMixParentAndChildInstances(): void
    {
        $parent = ConfiguredSingletonOperator::configure('https://parent.example.com');
        $child = ChildSingletonOperator::getInstance('https://child.example.com');
        self::assertSame(ChildSingletonOperator::class, get_class($child));
        self::assertSame($parent, ConfiguredSingletonOperator::getInstance());
        self::assertSame($child, ChildSingletonOperator::getInstance());
    }
}

/** Моделирует пользовательское расширение с собственным защищённым Singleton-слотом. */
class ConfiguredSingletonOperator extends Operator
{
    /** @var Operator|null Заранее настраиваемый экземпляр расширения. */
    protected static ?Operator $_instance = null;

    /** Настраивает экземпляр без обращения к getInstance.
     * @param string $host Хост нового экземпляра.
     * @return static Настроенный экземпляр расширения.
     * @throws \Exception При ошибке конструктора.
     */
    public static function configure(string $host): static
    {
        return static::$_instance = new static($host);
    }
}

/** Проверяет наследование того же слота ещё одним классом клиента. */
class ChildSingletonOperator extends ConfiguredSingletonOperator
{
}
